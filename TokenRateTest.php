<?

require_once("TokenRate.php");

error_reporting(E_ALL);

class TokenRateTest extends PHPUnit_Framework_TestCase {
   public function testRate() {
      $rate = new TokenRate(1, new DateInterval("PT01S"));
      $this->assertSame(1.0, $rate->getRate());
   }

   public function testLargerRate() {
      $rate = new TokenRate(10, new DateInterval("PT10S"));
      $this->assertSame(1.0, $rate->getRate());
   }

   public function testZeroRate() {
      $rate = new TokenRate(0, new DateInterval("PT10S"));
      $this->assertSame(0.0, $rate->getRate());
   }

   /**
    * I'm not really sure what this means for token buckets, but it's still a
    * rate.
    */
   public function testNegativeTokenRate() {
      $rate = new TokenRate(-5, new DateInterval("PT10S"));
      $this->assertSame(-0.5, $rate->getRate());
   }

   /**
    * I'm not sure when negative time means for token buckets, but it's still a
    * rate.
    */
   public function testNegativeTimeRate() {
      $interval = new DateInterval("PT10S");
      $interval->invert = true;
      $rate = new TokenRate(10, $interval);
      $this->assertSame(-1.0, $rate->getRate());
   }
}
