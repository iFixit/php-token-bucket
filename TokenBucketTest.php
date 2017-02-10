<?

namespace iFixit\TokenBucket;

error_reporting(E_ALL);

use PHPUnit_Framework_TestCase;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TokenBucket.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'TokenRate.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'StaticCache.php';

class TokenBucketTest extends PHPUnit_Framework_TestCase {
   public function testGetSingleToken() {
      $backend = new StaticCache();
      $identifier = "test_get";
      $rate = new TokenRate(1, 0);
      $bucket = new TokenBucket($identifier, $backend, $rate);

      $this->assertSame(1, $bucket->getTokens());
   }

   public function testGetMultipleTokens() {
      $backend = new StaticCache();
      $identifier = "test_get_multiple";
      $rate = new TokenRate(10000, 0);
      $bucket = new TokenBucket($identifier, $backend, $rate);

      $this->assertSame(10000, $bucket->getTokens());
   }

   public function testConsumeSingleToken() {
      $backend = new StaticCache();
      $identifier = "test_consume";
      $rate = new TokenRate(10000, 0);
      $bucket = new TokenBucket($identifier, $backend, $rate);

      list($consumed, $timeUntilReady) = $bucket->consume(1);
      $this->assertTrue(is_bool($consumed));
      $this->assertTrue(is_double($timeUntilReady));
      $readyTimestamp = microtime(true) + $timeUntilReady;
      $this->assertTrue($consumed, "Didn't consume token.");
      $this->assertTrue(round($timeUntilReady) >= 0,
       "TimeuntilReady is not now");

      $this->assertSame(9999.0, $bucket->getTokens());
   }

   public function testConsumeManyTokens() {
      $backend = new StaticCache();
      $identifier = "test_fail_consume";
      $rate = new TokenRate(PHP_INT_MAX, 0);
      $bucket = new TokenBucket($identifier, $backend, $rate);

      list($consumed, $timeUntilReady) = $bucket->consume(PHP_INT_MAX);
      $this->assertTrue(is_bool($consumed));
      $this->assertTrue(is_double($timeUntilReady));
      $this->assertTrue($consumed, "Didn't consume a token.");
      $this->assertTrue(0 < round($timeUntilReady), "Ready when the bucket
       shouldn't be");
      $this->assertSame(0.0, $bucket->getTokens());
   }

   public function testFailureToConsume() {
      $backend = new StaticCache();
      $identifier = "test_fail_consume";
      $rate = new TokenRate(0, 0);
      $bucket = new TokenBucket($identifier, $backend, $rate);

      list($consumed, $timeUntilReady) = $bucket->consume(1);
      $this->assertTrue(is_bool($consumed));
      $this->assertSame(null, $timeUntilReady, "Token bucket shouldn't have a
       ready time when it will never be able to regenerate enough tokens to
       accomidate the amount of the consume attempted.");
      $this->assertFalse($consumed, "Consumed a token when it shouldn't have.");
   }

   public function testTokenRegeneration() {
      $backend = new StaticCache();
      $identifier = "test_token_regen";
      $rate = new TokenRate(PHP_INT_MAX, 1);
      $bucket = new TokenBucket($identifier, $backend, $rate);

      list($consumed, $timeUntilReady) = $bucket->consume(PHP_INT_MAX);
      $this->assertTrue(is_bool($consumed));
      $this->assertTrue(is_double($timeUntilReady));
      $this->assertTrue($consumed, "Didn't consume a token.");
      $this->assertTrue(round($timeUntilReady) >= 0,
       "Time until ready is after now");
      sleep(1);
      $this->assertTrue($bucket->getTokens() > 0, "didn't regen tokens");
   }
}
