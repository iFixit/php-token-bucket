<?

namespace iFixit\TokenBucket;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'StoredBucket.php';

interface Backend {
   /**
    * Value for when there was no key found in storage.
    */
   const MISS = null;

   /**
    * Retrieve the value of the given string key.
    * Needs to return an StoredBucket.
    *
    * should return a StoredBucket
    */
   public function get($key);
   /**
    * Set the value of the given string key to whatever type value is.
    *
    * Expiration time is given as an integer number of seconds.
    */
   public function set($key, StoredBucket $value, $expirationTime);
}
