<?php

use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\BucketSettings;

$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");

$cluster = new Cluster("couchbase://localhost", $opts);
$bucket = $cluster->bucket("travel-sample");
$collection = $bucket->defaultCollection();

// tag::createBucketMgr[]
$bucketMgr = $cluster->buckets();
// end::createBucketMgr[]

printf("Creating bucket: hello\n");
try {
    // tag::createBucket[]
    $settings = new BucketSettings();
    $settings->setName("hello");
    $settings->setRamQuotaMb(200);
    $settings->setNumReplicas(1);
    $settings->enableFlush(true);

    $bucketMgr->createBucket($settings);
    // end::createBucket[]
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}

printf("Flushing bucket: hello\n");
try {
    // tag::flushBucket[]
    $bucketMgr->flush("hello");
    // end::flushBucket[]
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}

printf("Deleting bucket: hello\n");
try {
    // tag::removeBucket[]
    $bucketMgr->removeBucket("hello");
    // end::removeBucket[]
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
