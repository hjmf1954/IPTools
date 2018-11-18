<?php
/**
 * The file <code>Network.php</code> implements the class Network with all the Network-IP-functions.
 * The original file made by Alisher Safarov contains very little documentation.
 * DoxyGen documentation added.
 *
 * Please note that the IP-class handles only simple IP-Addresses and not Network-Addresses!
 * Please note that the Network-class handles the IP-Addresses with the CIDR or prefeix length.
 *
 * CIDR notation (i.e.192.0.0/24)
 * It's called CIDR (Classless Inter-Domain Routing) notation.
 * It's also commonly referred to as the prefix length.
 * The number after the slash represents the number of consecutive 1's in the subnet mask.
 * For example, 192.168.10.0/24 is equal to the network 192.168.10.0 with a 255.255.255.0 subnet mask or the IP-range 192.168.10.0 - 192.168.10.255
 * More examples:
 * 192.168.10.0/31 : 192.169.10.0 - 192.169.10.1   (  2)
 * 192.168.10.0/30 : 192.169.10.0 - 192.169.10.3   (  4)
 * 192.168.10.0/29 : 192.169.10.0 - 192.169.10.7   (  8)
 * 192.168.10.0/28 : 192.169.10.0 - 192.169.10.15  ( 15)
 * 192.168.10.0/27 : 192.169.10.0 - 192.169.10.31  ( 32)
 * 192.168.10.0/26 : 192.169.10.0 - 192.169.10.63  ( 64)
 * 192.168.10.0/25 : 192.169.10.0 - 192.169.10.127 (128)
 * 192.168.10.0/24 : 192.169.10.0 - 192.169.10.255 (256)
 * 192.168.10.0/23 : 192.169.10.0 - 192.169.11.255 (512)
 *
 * @file Network.php
 * @ingroup IPTools
 * @licence GNU GPL v2+
 * @ingroup phpinclude
 * @package IPTools
 *
 * @author Safarov Alisher, Harm Frielink, Nordhorn, Germany
 * @author Safarov Alisher<alisher.safarov@outlook.com>, Harm Frielink <harm@harmfrielink.nl>
 * @copyright 2009-2018 Safarov Alisher, Harm Frielink
 *
 * @link https://github.com/S1lentium/IPTools, Forked from S1lentium/IPTools: https://github.com/hjmf1954/IPTools.git
 */

namespace IPTools;

/**
 * Class <code>Network</code> implements the Tools for manipulation Network-IP-addresses.
 * Supports IPv4 and IPv6.
 * Supports Subclasses.
 *
 * Version:
 * - 1.0.1.0 - 17 Jan 2018 - Original version by Alisher Safarov.
 * - 1.0.1.1 - 16 Nov 2018 - Docu + Versioning.
 */
class Network implements \Iterator, \Countable {
   use PropertyTrait;

   /**
    * Versioning Number.
    */
   const versionNumber = "1.0.1.1";

   /**
    * Versioning Date.
    */
   const versionDate = 20181116;

   /**
    * Versioning, last updated.
    */
   const lastUpdated = "Fri 16 Nov 2018";

   /**
    * @var string Requested IP-Address
    */
   private $ip;

   /**
    * @var string network mask.
    */
   private $netmask;

   /**
    * @var int
    */
   private $position = 0;

   /**
    * Constructor.
    * @param IP $ip      Requested IP-Address which may NOT contain a CIDR or prefix length (i.e. 192.168.178.0).
    * @param IP $netmask Requested Netmask (i.e. 255.255.255.0).
    */
   public function __construct(IP $ip, IP $netmask) {
      $this->setIP($ip);
      $this->setNetmask($netmask);
   } // __construct

   /**
    * Implements the toString.
    * @return string
    */
   public function __toString() {
      return $this->getCIDR();
   } // __toString

   /**
    * Parses ipaddresses which may contain
    * - CIDR-notation. '192.168.0.54/24'             =>  range 192.168.0.0 - 192.168.0.255 = 192.168.0.0/24
    * - Subnet mask    '127.168.0.1 255.255.255.255' =>  range 127.168.0.1                 = 127.168.0.1/32
    * - ??             '1234::1234'                  => 1234::1234/128
    * @param  string    $data An IP-Address like
    * @return Network Instance
    */
   public static function parse($data) {
      if (preg_match('~^(.+?)/(\d+)$~', $data, $matches)) {
         $ip = IP::parse($matches[1]);
         $netmask = self::prefix2netmask((int) $matches[2], $ip->getVersion());
      } elseif (strpos($data, ' ')) {
         list($ip, $netmask) = explode(' ', $data, 2);
         $ip = IP::parse($ip);
         $netmask = IP::parse($netmask);
      } else {
         $ip = IP::parse($data);
         $netmask = self::prefix2netmask($ip->getMaxPrefixLength(), $ip->getVersion());
      }

      return new self($ip, $netmask);
   } // parse

   /**
    * Converts the CIDR to the netmask.
    * - 24, IPv4 => 255.255.255.0
    * @param  int          $prefixLength
    * @param  string       $version
    * @throws \Exception
    * @return IP
    */
   public static function prefix2netmask($prefixLength, $version) {
      if ( ! in_array($version, array(IP::IP_V4, IP::IP_V6))) {
         throw new \Exception("Wrong IP version");
      }

      $maxPrefixLength = $version === IP::IP_V4
         ? IP::IP_V4_MAX_PREFIX_LENGTH
         : IP::IP_V6_MAX_PREFIX_LENGTH;

      if ( ! is_numeric($prefixLength)
         || ! ($prefixLength >= 0 && $prefixLength <= $maxPrefixLength)
      ) {
         throw new \Exception('Invalid prefix length');
      }

      $binIP = str_pad(str_pad('', (int) $prefixLength, '1'), $maxPrefixLength, '0');

      return IP::parseBin($binIP);
   } // prefix2netmask

   /**
    * Converts a NetMask (255.255.255.0) into a CIDR (24).
    * @param  IP    ip Instance of the IP-Class.
    * @return int CIDR number
    */
   public static function netmask2prefix(IP $ip) {
      return strlen(rtrim($ip->toBin(), 0));
   }

   /**
    * Sets the member variable $ip to an instance of class IP.
    * @param  IP           ip Class Instance of IP.
    * @throws \Exception
    */
   public function setIP(IP $ip) {
      if (isset($this->netmask) && $this->netmask->getVersion() !== $ip->getVersion()) {
         throw new \Exception('[Network::setIP] - IP version is not same as Netmask version');
      }

      $this->ip = $ip;
   } // setIP

   /**
    * Sets the member variable $netmask.
    * @param  IP           ip Class Instance of IP.
    * @throws \Exception
    */
   public function setNetmask(IP $ip) {
      if ( ! preg_match('/^1*0*$/', $ip->toBin())) {
         throw new \Exception('Invalid Netmask address format');
      }

      if (isset($this->ip) && $ip->getVersion() !== $this->ip->getVersion()) {
         throw new \Exception('Netmask version is not same as IP version');
      }

      $this->netmask = $ip;
   } // setNetMask

   /**
    * Sets the member variable $netmask.
    * @param int $prefixLength
    */
   public function setPrefixLength($prefixLength) {
      $this->setNetmask(self::prefix2netmask((int) $prefixLength, $this->ip->getVersion()));
   }

   /**
    * Getter for the IP-Class-Instance.
    * @return IP
    */
   public function getIP() {
      return $this->ip;
   }

   /**
    * Getter for the netmask.
    * @return IP
    */
   public function getNetmask() {
      return $this->netmask;
   }

   /**
    * Getter for a new Class Instance of IP.
    * @return IP
    */
   public function getNetwork() {
      return new IP(inet_ntop($this->getIP()->inAddr() & $this->getNetmask()->inAddr()));
   }

   /**
    * Getter for the Prefix length (CIDR-number).
    * @return int
    */
   public function getPrefixLength() {
      return self::netmask2prefix($this->getNetmask());
   }

   /**
    * Gets the full CIDR (192.0.0.0/32).
    * @return string
    */
   public function getCIDR() {
      return sprintf('%s/%s', $this->getNetwork(), $this->getPrefixLength());
   }

   /**
    * Gets the IP-Class Instance from wildcart notation.
    * @return IP
    */
   public function getWildcard() {
      return new IP(inet_ntop(~$this->getNetmask()->inAddr()));
   }

   /**
    * Gets the IP-Class Instance from Broadcast.
    * @return IP
    */
   public function getBroadcast() {
      return new IP(inet_ntop($this->getNetwork()->inAddr() | ~$this->getNetmask()->inAddr()));
   }

   /**
    * Gets the first IP of a range.
    * @return IP Class instance of IP.
    */
   public function getFirstIP() {
      return $this->getNetwork();
   }

   /**
    * Gets the last IP of a range.
    * @return IP
    */
   public function getLastIP() {
      return $this->getBroadcast();
   }

   /**
    * @return int|string
    */
   public function getBlockSize() {
      $maxPrefixLength = $this->ip->getMaxPrefixLength();
      $prefixLength = $this->getPrefixLength();

      if ($this->ip->getVersion() === IP::IP_V6) {
         return bcpow('2', (string) ($maxPrefixLength - $prefixLength));
      }

      return pow(2, $maxPrefixLength - $prefixLength);
   }

   /**
    * @return Range
    */
   public function getHosts() {
      $firstHost = $this->getNetwork();
      $lastHost = $this->getBroadcast();

      if ($this->ip->getVersion() === IP::IP_V4) {
         if ($this->getBlockSize() > 2) {
            $firstHost = IP::parseBin(substr($firstHost->toBin(), 0, $firstHost->getMaxPrefixLength() - 1) . '1');
            $lastHost = IP::parseBin(substr($lastHost->toBin(), 0, $lastHost->getMaxPrefixLength() - 1) . '0');
         }
      }

      return new Range($firstHost, $lastHost);
   }

   /**
    * @param  IP|Network   $exclude
    * @throws \Exception
    * @return Network[]
    */
   public function exclude($exclude) {
      $exclude = self::parse($exclude);

      if (strcmp($exclude->getFirstIP()->inAddr(), $this->getLastIP()->inAddr()) > 0
         || strcmp($exclude->getLastIP()->inAddr(), $this->getFirstIP()->inAddr()) < 0
      ) {
         throw new \Exception('Exclude subnet not within target network');
      }

      $networks = array();

      $newPrefixLength = $this->getPrefixLength() + 1;
      if ($newPrefixLength > $this->ip->getMaxPrefixLength()) {
         return $networks;
      }

      $lower = clone $this;
      $lower->setPrefixLength($newPrefixLength);

      $upper = clone $lower;
      $upper->setIP($lower->getLastIP()->next());

      while ($newPrefixLength <= $exclude->getPrefixLength()) {
         $range = new Range($lower->getFirstIP(), $lower->getLastIP());
         if ($range->contains($exclude)) {
            $matched = $lower;
            $unmatched = $upper;
         } else {
            $matched = $upper;
            $unmatched = $lower;
         }

         $networks[] = clone $unmatched;

         if (++$newPrefixLength > $this->getNetwork()->getMaxPrefixLength()) {
            break;
         }

         $matched->setPrefixLength($newPrefixLength);
         $unmatched->setPrefixLength($newPrefixLength);
         $unmatched->setIP($matched->getLastIP()->next());
      }

      sort($networks);

      return $networks;
   }

   /**
    * @param  int          $prefixLength
    * @throws \Exception
    * @return Network[]
    */
   public function moveTo($prefixLength) {
      $maxPrefixLength = $this->ip->getMaxPrefixLength();

      if ($prefixLength <= $this->getPrefixLength() || $prefixLength > $maxPrefixLength) {
         throw new \Exception('Invalid prefix length ');
      }

      $netmask = self::prefix2netmask($prefixLength, $this->ip->getVersion());
      $networks = array();

      $subnet = clone $this;
      $subnet->setPrefixLength($prefixLength);

      while ($subnet->ip->inAddr() <= $this->getLastIP()->inAddr()) {
         $networks[] = $subnet;
         $subnet = new self($subnet->getLastIP()->next(), $netmask);
      }

      return $networks;
   }

   /**
    * @return IP
    */
   public function current() {
      return $this->getFirstIP()->next($this->position);
   }

   /**
    * @return int
    */
   public function key() {
      return $this->position;
   }

   public function next() {
      ++$this->position;
   }

   public function rewind() {
      $this->position = 0;
   }

   /**
    * @return bool
    */
   public function valid() {
      return strcmp($this->getFirstIP()->next($this->position)->inAddr(), $this->getLastIP()->inAddr()) <= 0;
   }

   /**
    * @return int
    */
   public function count() {
      return (integer) $this->getBlockSize();
   }

}
