<?php
use Couchbase\ClusterOptions;
use Couchbase\Cluster;
use Couchbase\CollectionSpec;

$connectionString = 'localhost';
$options = new ClusterOptions();
$options->credentials('Administrator', 'password');

$cluster = new Cluster($connectionString, $options);
$bucket = $cluster->bucket('travel-sample');

$collectionMgr = $bucket->collections();

$spec = new CollectionSpec();
$spec->setName('bookings');
$spec->setScopeName('_default');

$collectionMgr->createCollection($spec);

// tag::collections_1[]
$bucket->scope('_default')->collection('bookings');
// end::collections_1[]

// tag::collections_2[]
$bucket->scope('tenant_agent_00')->collection('bookings');
// end::collections_2[]
