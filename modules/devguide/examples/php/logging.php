<?php

require_once '../../../vendor/autoload.php';

use Couchbase\Cluster;
use Couchbase\ClusterOptions;

// tag::logging[]
$options = new ClusterOptions();
$options->credentials("Administrator", "password");

$cluster = new Cluster('couchbase://localhost', $options);
$bucket = $cluster->bucket('travel-sample');

$collection = $bucket->scope('inventory')->collection('airline');
$getResult = $collection->get('airline_10');

print_r($getResult->content());
// end::logging[]
