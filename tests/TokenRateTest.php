<?php

namespace iFixit\TokenBucket\Tests;

error_reporting(E_ALL);

use \DateInterval;

use iFixit\TokenBucket\TokenRate;
use iFixit\TokenBucket\TokenBucket;

class TokenRateTest extends \PHPUnit\Framework\TestCase {
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

   /**
    * When DateInterval was using anything but seconds was a problem,
    * because the DateInterval class won't convert itself into purely seconds
    * for something like $interval->s. Assert that time larger than
    * seconds works.
    */
   public function testPeriodRate() {
      $rate = new TokenRate(60, 10);
      $this->assertSame(6.0, $rate->getRate());

      $rate = new TokenRate(2, 3600 * 24 * 30);
      $this->assertSame(2 / (3600 * 24 * 30), $rate->getRate());
   }

   public function testGetters() {
      $rate = new TokenRate(60, 10);
      $this->assertSame(60, $rate->getTokens());
      $this->assertSame(10, $rate->getSeconds());
   }
}
