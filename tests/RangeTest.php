<?php

use IPTools\IP;
use IPTools\Network;
use IPTools\Range;

class RangeTest extends \PHPUnit_Framework_TestCase {

   public function testVersion() {
      $this->assertEquals( "1.0.1.1", Range::versionNumber, "Not correct versionNumber");

      $ip6CIDR = "2a02:8108::/31";
      $range   = Range::parse($ip6CIDR);

      $firstIP = $range->firstIP->__toString();
      printf("\n");
      printf("%s => %s - %s\n", $ip6CIDR, $firstIP, $range->lastIP );

      $ip = IP::parse("2102:8108::");
      printf("%s => %s\n", "2102:8108::", IP::parse("2102:8188::")->__toString() );
      printf("%s => ", "2102:8108::");
      printf( $ip->inAddr());
      printf("\n");
   }

   /**
    * Tests the 'parse' function.
    * @dataProvider getTestParseData
    */
   public function testParse($data, $expected) {
      $range = Range::parse($data);

      $this->assertEquals($expected[0], $range->firstIP);
      $this->assertEquals($expected[1], $range->lastIP);
   }

   /**
    * Tests range of IP-Addresses (with CIDR)
    * @dataProvider getTestNetworksData
    */
   public function testGetNetworks($data, $expected) {
      $result = array();

      foreach (Range::parse($data)->getNetworks() as $network) {
         $result[] = (string) $network;
      }

      $this->assertEquals($expected, $result);
   }

   /**
    * @dataProvider getTestContainsData
    */
   public function testContains($data, $find, $expected) {
      $this->assertEquals($expected, Range::parse($data)->contains(new IP($find)));
   }

   /**
    * @dataProvider getTestIterationData
    */
   public function testRangeIteration($data, $expected) {
      foreach (Range::parse($data) as $key => $ip) {
         $result[] = (string) $ip;
      }

      $this->assertEquals($expected, $result);
   }

   /**
    * @dataProvider getTestCountData
    */
   public function testCount($data, $expected) {
      $this->assertEquals($expected, count(Range::parse($data)));
   }

   public function getTestParseData() {
      return array(
         array('127.0.0.1-127.255.255.255', array('127.0.0.1', '127.255.255.255')),
         array('127.0.0.1/24', array('127.0.0.0', '127.0.0.255')),
         array('127.*.0.0', array('127.0.0.0', '127.255.0.0')),
         array('127.255.255.0', array('127.255.255.0', '127.255.255.0')),
         // array("2a02:8108::/31",array('2a02:8108:0:0:0:0:0:0', '2a02:8109:ffff:ffff:ffff:ffff:ffff:ffff') ),
      );
   }

   /**
    * Getter for 'testGetNetworks'.
    * @see testGetNetworks
    * @return [type] [description]
    */
   public function getTestNetworksData() {
      return array(
         array('192.168.1.*', array('192.168.1.0/24')),  // Wildcart
         array('192.168.1.208-192.168.1.255', array(     // Range
            '192.168.1.208/28',
            '192.168.1.224/27',
         )),
         array('192.168.1.0-192.168.1.191', array(       // Range
            '192.168.1.0/25',
            '192.168.1.128/26',
         )),
         array('192.168.1.125-192.168.1.126', array(     // Range
            '192.168.1.125/32',
            '192.168.1.126/32',
         )),
      );
   }

   public function getTestContainsData() {
      return array(
         array('192.168.*.*', '192.168.245.15', true),
         array('192.168.*.*', '192.169.255.255', false),

         /**
          * 10.10.45.48 --> 00001010 00001010 00101101 00110000
          * the last 0000 leads error
          */
         array('10.10.45.48/28', '10.10.45.58', true),

         array('2001:db8::/64', '2001:db8::ffff', true),
         array('2001:db8::/64', '2001:db8:ffff::', false),
      );
   }

   public function getTestIterationData() {
      return array(
         array('192.168.2.0-192.168.2.7',
            array(
               '192.168.2.0',
               '192.168.2.1',
               '192.168.2.2',
               '192.168.2.3',
               '192.168.2.4',
               '192.168.2.5',
               '192.168.2.6',
               '192.168.2.7',
            ),
         ),
         array('2001:db8::/125',
            array(
               '2001:db8::',
               '2001:db8::1',
               '2001:db8::2',
               '2001:db8::3',
               '2001:db8::4',
               '2001:db8::5',
               '2001:db8::6',
               '2001:db8::7',
            ),
         ),
      );
   }

   public function getTestCountData() {
      return array(
         array('127.0.0.0/31'          , 2),
         array('2001:db8::/120'        , 256),
         array('2a02:8108:4:3:2:1::'   ,   1),
         array('2a02:8108:4:3:2::/127' ,   2),
         array('2a02:8108:4:3:2::/126' ,   4),
         array('2a02:8108:4:3:2::/125' ,   8),
         array('2a02:8108:4:3:2::/124' ,  16),
         array('2a02:8108:4:3:2::/120' , 256),
         array('2a02:8108::/31'        , 9223372036854775807),
      );
   }

}
