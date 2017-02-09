<?

namespace iFixit\TokenBucket;

error_reporting(E_ALL);

use \DateInterval;

use PHPUnit_Framework_TestCase;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TokenRate.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'TokenBucket.php';


class TokenRateTest extends PHPUnit_Framework_TestCase {
   public function testRate() {
      $rate = new TokenRate(1, 1);
      $this->assertSame(1.0, $rate->getRate());
   }

   public function testLargerRate() {
      $rate = new TokenRate(10, 10);
      $this->assertSame(1.0, $rate->getRate());
   }

   public function testZeroRate() {
      $rate = new TokenRate(0, 10);
      $this->assertSame(0, $rate->getRate());
   }

   /**
    * I'm not really sure what this means for token buckets, but it's still a
    * rate.
    */
   public function testNegativeTokenRate() {
      $rate = new TokenRate(-5, 10);
      $this->assertSame(-0.5, $rate->getRate());
   }

   /**
    * I'm not sure what negative time means for token buckets, but it's still a
    * rate.
    */
   public function testNegativeTimeRate() {
      $rate = new TokenRate(10, -10);
      $this->assertSame(-1.0, $rate->getRate());
   }
}
