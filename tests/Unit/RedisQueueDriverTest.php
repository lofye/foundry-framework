<?php

declare(strict_types=1);

namespace {
    if (!class_exists('Redis', false)) {
        class Redis
        {
            public function rPush(string $key, mixed ...$values): int|false
            {
                return 1;
            }

            public function lPop(string $key): mixed
            {
                return false;
            }

            /**
             * @param list<string> $keys
             */
            public function blPop(array $keys, int|float $timeout): mixed
            {
                return false;
            }

            public function lRange(string $key, int $start, int $end): array|false
            {
                return [];
            }
        }
    }
}

namespace Foundry\Tests\Unit {

    use Foundry\Queue\RedisQueueDriver;
    use Foundry\Support\FoundryError;
    use Foundry\Support\Json;
    use PHPUnit\Framework\TestCase;

    final class RedisQueueDriverTest extends TestCase
    {
        public function test_enqueue_pushes_encoded_job_to_normalized_queue_key(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->expects($this->once())
                ->method('rPush')
                ->with(
                    'custom:emails',
                    Json::encode(['job' => 'send_welcome', 'payload' => ['user_id' => 42]]),
                )
                ->willReturn(1);

            (new RedisQueueDriver($redis, prefix: 'custom:::'))->enqueue('emails', 'send_welcome', ['user_id' => 42]);
        }

        public function test_enqueue_throws_when_redis_rejects_write(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->method('rPush')->willReturn(false);

            $error = $this->expectFoundryError(function () use ($redis): void {
                (new RedisQueueDriver($redis))->enqueue('default', 'job', []);
            });

            $this->assertSame('REDIS_ENQUEUE_FAILED', $error->errorCode);
            $this->assertSame(['queue' => 'default'], $error->details);
        }

        public function test_non_blocking_dequeue_returns_decoded_job_record(): void
        {
            $record = Json::encode(['job' => 'send_welcome', 'payload' => ['user_id' => 42]]);
            $redis = $this->createMock(\Redis::class);
            $redis->expects($this->once())
                ->method('lPop')
                ->with('foundry:queue:default')
                ->willReturn($record);

            $job = (new RedisQueueDriver($redis))->dequeue('default');

            $this->assertSame(['job' => 'send_welcome', 'payload' => ['user_id' => 42]], $job);
        }

        public function test_non_blocking_dequeue_returns_null_for_empty_queue(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->method('lPop')->willReturn(false);

            $this->assertNull((new RedisQueueDriver($redis))->dequeue('default'));
        }

        public function test_non_blocking_dequeue_rejects_non_string_payload(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->method('lPop')->willReturn(['not-json']);

            $error = $this->expectFoundryError(function () use ($redis): void {
                (new RedisQueueDriver($redis))->dequeue('default');
            });

            $this->assertSame('REDIS_DEQUEUE_INVALID', $error->errorCode);
        }

        public function test_blocking_dequeue_returns_decoded_job_record(): void
        {
            $record = Json::encode(['job' => 'send_digest', 'payload' => ['count' => 3]]);
            $redis = $this->createMock(\Redis::class);
            $redis->expects($this->once())
                ->method('blPop')
                ->with(['foundry:queue:default'], 5)
                ->willReturn(['foundry:queue:default', $record]);

            $job = (new RedisQueueDriver($redis, blockSeconds: 5))->dequeue('default');

            $this->assertSame(['job' => 'send_digest', 'payload' => ['count' => 3]], $job);
        }

        public function test_blocking_dequeue_returns_null_for_empty_results(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->method('blPop')->willReturn(['foundry:queue:default']);

            $this->assertNull((new RedisQueueDriver($redis, blockSeconds: 5))->dequeue('default'));
        }

        public function test_blocking_dequeue_rejects_invalid_raw_record(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->method('blPop')->willReturn(['foundry:queue:default', ['not-json']]);

            $error = $this->expectFoundryError(function () use ($redis): void {
                (new RedisQueueDriver($redis, blockSeconds: 5))->dequeue('default');
            });

            $this->assertSame('REDIS_DEQUEUE_INVALID', $error->errorCode);
        }

        public function test_dequeue_rejects_decoded_payload_without_job_and_payload_shape(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->method('lPop')->willReturn(Json::encode(['job' => ['not-string'], 'payload' => 'not-array']));

            $error = $this->expectFoundryError(function () use ($redis): void {
                (new RedisQueueDriver($redis))->dequeue('default');
            });

            $this->assertSame('REDIS_DEQUEUE_INVALID', $error->errorCode);
        }

        public function test_inspect_returns_valid_records_and_skips_invalid_records(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->expects($this->once())
                ->method('lRange')
                ->with('foundry:queue:default', 0, -1)
                ->willReturn([
                    Json::encode(['job' => 'valid_one', 'payload' => ['a' => 1]]),
                    ['not-string'],
                    '{invalid-json',
                    Json::encode(['job' => 'valid_two', 'payload' => ['b' => 2]]),
                    Json::encode(['job' => 'invalid_shape', 'payload' => 'not-array']),
                ]);

            $records = (new RedisQueueDriver($redis))->inspect('default');

            $this->assertSame([
                ['job' => 'valid_one', 'payload' => ['a' => 1]],
                ['job' => 'valid_two', 'payload' => ['b' => 2]],
            ], $records);
        }

        public function test_inspect_returns_empty_list_when_redis_reply_is_not_a_list(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->method('lRange')->willReturn(false);

            $this->assertSame([], (new RedisQueueDriver($redis))->inspect('default'));
        }

        /**
         * @param callable():void $callback
         */
        private function expectFoundryError(callable $callback): FoundryError
        {
            try {
                $callback();
            } catch (FoundryError $error) {
                return $error;
            }

            self::fail('Expected FoundryError was not thrown.');
        }
    }
}
