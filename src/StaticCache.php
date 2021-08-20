<?php

namespace iFixit\TokenBucket;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Backend.php';

/**
 * Simple implementation of Backend that doesn't attempt to expire anything
 * made for the sake of testing.
 */
class StaticCache implements Backend {
   private static $cache = [];

   public function get($key) {
      return array_key_exists($key, self::$cache) ?
       self::$cache[$key] : Backend::MISS;
   }

   public function set($key, StoredBucket $bucket, $expiration = 0) {
      self::$cache[$key] = $bucket;
   }
}
