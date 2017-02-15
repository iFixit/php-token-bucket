<?

namespace iFixit\TokenBucket;

error_reporting(E_ALL);

use PHPUnit_Framework_TestCase;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestTokenBucket.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'TokenRate.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'StaticCache.php';

class TokenBucketTest extends PHPUnit_Framework_TestCase {
   public function testGetSingleToken() {
      $backend = new StaticCache();
      $identifier = "test_get";
      $rate = new TokenRate(1, 0);
      $bucket = new TestTokenBucket($identifier, $backend, $rate);

      $this->assertSame(1, $bucket->getTokens());
   }

   public function testGetMultipleTokens() {
      $backend = new StaticCache();
      $identifier = "test_get_multiple";
      $rate = new TokenRate(10000, 0);
      $bucket = new TestTokenBucket($identifier, $backend, $rate);

      $this->assertSame(10000, $bucket->getTokens());
   }

   public function testConsumeSingleToken() {
      $backend = new StaticCache();
      $identifier = "test_consume";
      $rate = new TokenRate(10000, 0);
      $bucket = new TestTokenBucket($identifier, $backend, $rate);

      list($consumed, $timeUntilReady) = $bucket->consume(1);
      $this->assertTrue(is_bool($consumed));
      $this->assertTrue(is_double($timeUntilReady));
      $readyTimestamp = microtime(true) + $timeUntilReady;
      $this->assertTrue($consumed, "Didn't consume token.");
      $this->assertTrue(round($timeUntilReady) >= 0,
       "TimeuntilReady is not now");

      $this->assertSame(9999, $bucket->getTokens());
   }

   public function testConsumeManyTokens() {
      $backend = new StaticCache();
      $identifier = "test_fail_consume";
      $rate = new TokenRate(PHP_INT_MAX, 0);
      $bucket = new TestTokenBucket($identifier, $backend, $rate);

      list($consumed, $timeUntilReady) = $bucket->consume(PHP_INT_MAX);
      $this->assertTrue(is_bool($consumed));
      $this->assertTrue(is_double($timeUntilReady));
      $this->assertTrue($consumed, "Didn't consume a token.");
      $this->assertTrue(0 < round($timeUntilReady), "Ready when the bucket
       shouldn't be");
      $this->assertSame(0, $bucket->getTokens());
   }

   public function testFailureToConsume() {
      $backend = new StaticCache();
      $identifier = "test_fail_consume";
      $rate = new TokenRate(0, 0);
      $bucket = new TestTokenBucket($identifier, $backend, $rate);

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
      $rate = new TokenRate(1, 1);
      $bucket = new TestTokenBucket($identifier, $backend, $rate);

      list($consumed, $timeUntilReady) = $bucket->consume(1);
      $this->assertTrue(is_bool($consumed));
      $this->assertTrue(is_double($timeUntilReady));
      $this->assertTrue($consumed, "Didn't consume a token.");
      $this->assertTrue(round($timeUntilReady) >= 0,
       "Time until ready is after now");

      list($consumed, $timeUntilReady) = $bucket->consume(1);
      $this->assertFalse($consumed);

      $bucket->setOffset(1);
      $this->assertTrue($bucket->getTokens() > 0, "didn't regen tokens");
   }

   /**
    * Make sure that when we consume we're getting the updated number of tokens
    * from the last consume.
    */
   public function testUpdatedTokensCalculated() {
      $backend = new StaticCache();
      $identifier = "test_updated_tokens_calculated";
      $rate = new TokenRate(10, 10);
      $bucket = new TestTokenBucket($identifier, $backend, $rate);

      list($consumed, $timeUntilReady) = $bucket->consume(10);
      $this->assertTrue($consumed);
      $this->assertTrue(is_double($timeUntilReady));

      $bucket->setOffset(5);
      // half should be regenerated
      list($consumed, $timeUntilReady) = $bucket->consume(10);
      $this->assertFalse($consumed);
      $this->assertEquals(5, $timeUntilReady);

      $bucket->setOffset(6);
      // More should be generated than we consume.
      list($consumed, $timeUntilReady) = $bucket->consume(5);
      $this->assertTrue($consumed);
      // it was ready
      $this->assertEquals(5, 0);
   }
}
