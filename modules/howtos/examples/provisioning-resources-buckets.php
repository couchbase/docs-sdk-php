<?php

require_once '../../../vendor/autoload.php';

use Couchbase\Management\BucketSettings;
use Couchbase\Management\BucketType;
use Couchbase\ClusterOptions;
use Couchbase\Cluster;
use Couchbase\Management\ConflictResolutionType;

function main()
{
	// tag::creating-bucket-mgr[]
	$connectionString = "couchbase://localhost";
	$options = new ClusterOptions();
	$options->credentials("Administrator", "password");
	$cluster = new Cluster($connectionString, $options);

	$bucketMgr = $cluster->buckets();
	// end::creating-bucket-mgr[]

	createBucket($bucketMgr);
	updateBucket($bucketMgr);
	// The examples can run quite fast, wait a few seconds before flushing.
	sleep(5);
	flushBucket($bucketMgr);
	removeBucket($bucketMgr);
}

function createBucket($bucketMgr)
{
	// tag::create-bucket[]
	$settings = new BucketSettings("hello");
	$settings->enableFlush(false);
	$settings->enableReplicaIndexes(false);
	$settings->setRamQuotaMb(150);
	$settings->setNumReplicas(1);
	$settings->setBucketType(BucketType::COUCHBASE);
	$settings->conflictResolutionType(ConflictResolutionType::SEQUENCE_NUMBER);

	$bucketMgr->createBucket($settings);
	// end::create-bucket[]
}

function updateBucket($bucketMgr)
{
	// tag::update-bucket[]
	$settings = $bucketMgr->getBucket("hello");
	$settings->enableFlush(true);

	$bucketMgr->updateBucket($settings);
	// end::update-bucket[]
}

function flushBucket($bucketMgr)
{
	// tag::flush-bucket[]
	$bucketMgr->flush("hello");
	// end::flush-bucket[]
}

function removeBucket($bucketMgr)
{
	// tag::remove-bucket[]
	$bucketMgr->dropBucket("hello");
	// end::remove-bucket[]
}

main();
