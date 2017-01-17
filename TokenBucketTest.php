<?

error_reporting(E_ALL);

require_once("TokenRate.php");
require_once("TokenBucket.php");
require_once("StaticCache.php");

class TokenBucketTest extends PHPUnit_Framework_TestCase {
   public function testGetSingleToken() {
      $backend = new StaticCache();
      $identifier = "test_get";
      $rate = new TokenRate(1, new DateInterval("PT0S"));
      $bucket = new TokenBucket($identifier, $backend, $rate);

      $this->assertSame(1, $bucket->getTokenCount());
   }

   public function testGetMultipleTokens() {
      $backend = new StaticCache();
      $identifier = "test_get_multiple";
      $rate = new TokenRate(10000, new DateInterval("PT0S"));
      $bucket = new TokenBucket($identifier, $backend, $rate);

      $this->assertSame(10000, $bucket->getTokenCount());
   }

   public function testConsumeSingleToken() {
      $backend = new StaticCache();
      $identifier = "test_consume";
      $rate = new TokenRate(10000, new DateInterval("PT0S"));
      $bucket = new TokenBucket($identifier, $backend, $rate);

      list($consumed, $timeUntilReady) = $bucket->consume(1);
      $this->assertTrue($consumed, "Didn't consume token.");
      $this->assertTrue($timeUntilReady->diff(new DateTime())->s >= 0,
       "Time until ready is after now");

      $this->assertSame(9999, $bucket->getTokenCount());
      $this->assertTrue($timeUntilReady->diff(new DateTime())->s >= 0);
   }

   public function testConsumeManyTokens() {
      $backend = new StaticCache();
      $identifier = "test_fail_consume";
      $rate = new TokenRate(PHP_INT_MAX, new DateInterval("PT0S"));
      $bucket = new TokenBucket($identifier, $backend, $rate);


      list($consumed, $timeUntilReady) = $bucket->consume(PHP_INT_MAX);
      $this->assertTrue($consumed, "Dindn't consume a token.");
      $this->assertSame(0, $timeUntilReady->diff(new DateTime())->s,
       "Time until ready is after now");
      $this->assertSame(0, $bucket->getTokenCount());
   }

   public function testFailureToConsume() {
      $backend = new StaticCache();
      $identifier = "test_fail_consume";
      $rate = new TokenRate(0, new DateInterval("PT0S"));
      $bucket = new TokenBucket($identifier, $backend, $rate);

      list($consumed, $_) = $bucket->consume(1);
      $this->assertFalse($consumed, "Consumed a token.");
   }

   public function testTokenRegeneration() {
      $backend = new StaticCache();
      $identifier = "test_token_regen";
      $rate = new TokenRate(PHP_INT_MAX, new DateInterval("PT1S"));
      $bucket = new TokenBucket($identifier, $backend, $rate);


      list($consumed, $timeUntilReady) = $bucket->consume(PHP_INT_MAX);
      $this->assertTrue($consumed, "Dindn't consume a token.");
      $this->assertTrue($timeUntilReady->diff(new DateTime())->s >= 0,
       "Time until ready is after now");
      sleep(1);
      $this->assertTrue($bucket->getTokenCount() > 0, "didn't regen tokens");
   }
}
