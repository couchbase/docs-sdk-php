<?php

require_once '../../../vendor/autoload.php';

use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\Management\CreateQueryPrimaryIndexOptions;
use Couchbase\Management\CreateQueryIndexOptions;
use Couchbase\Management\BuildQueryIndexesOptions;
use Couchbase\Management\DropQueryIndexOptions;
use Couchbase\Management\DropQueryPrimaryIndexOptions;
use Couchbase\Management\WatchQueryIndexesOptions;

function main()
{
    // tag::creating-index-mgr[]
    $options = new ClusterOptions();
    $options->credentials("Administrator", "password");
    $cluster = new Cluster("couchbase://localhost", $options);

    $queryIndexMgr = $cluster->queryIndexes();
    // end::creating-index-mgr[]

    primaryIndex($queryIndexMgr);
    secondaryIndex($queryIndexMgr);
    deferAndWatchIndex($queryIndexMgr);
    dropPrimaryIndex($queryIndexMgr);
}

function primaryIndex($queryIndexMgr)
{
    print "Example - [primary]\n";
    // tag::primary[]
    $options = new CreateQueryPrimaryIndexOptions();
    $options->scopeName("tenant_agent_01");
    $options->collectionName("users");
    // Set this if you wish to use a custom name
    // $options->indexName("custom_name");
    $options->ignoreIfExists(true);

    $queryIndexMgr->createPrimaryIndex("travel-sample", $options);
    // end::primary[]
}

function secondaryIndex($queryIndexMgr)
{
    print "\nExample - [secondary]\n";
    try {
        // tag::secondary[]
        $options = new CreateQueryIndexOptions();
        $options->scopeName("tenant_agent_01");
        $options->collectionName("users");

        $queryIndexMgr->createIndex("travel-sample", "tenant_agent_01_users_email", ["preferred_email"], $options);
        // end::secondary[]
    } catch (Couchbase\Exception\IndexExistsException) {
        print "Index already exists\n";
    }
}

function deferAndWatchIndex($queryIndexMgr)
{
    print "\nExample - [defer-indexes]\n";
    try {
        // tag::defer-indexes[]
        // Create a deferred index
        $createOpts = new CreateQueryIndexOptions();
        $createOpts->scopeName("tenant_agent_01");
        $createOpts->collectionName("users");
        $createOpts->deferred(true);

        $queryIndexMgr->createIndex("travel-sample", "tenant_agent_01_users_phone", ["preferred_phone"], $createOpts);

        // Build any deferred indexes within `travel-sample`.tenant_agent_01.users
        $deferredOpts = new BuildQueryIndexesOptions();
        $deferredOpts->scopeName("tenant_agent_01");
        $deferredOpts->collectionName("users");

        $queryIndexMgr->buildDeferredIndexes("travel-sample", $deferredOpts);

        // Wait for indexes to come online
        $watchOpts = new WatchQueryIndexesOptions();
        $watchOpts->scopeName("tenant_agent_01");
        $watchOpts->collectionName("users");

        $queryIndexMgr->watchIndexes("travel-sample", ["tenant_agent_01_users_phone"], 30000, $watchOpts);
        // end::defer-indexes[]
    } catch (Couchbase\Exception\IndexExistsException) {
        print "Index already exists\n";
    }
}

function dropPrimaryIndex($queryIndexMgr)
{
    print "\nExample - [drop-primary-or-secondary-index]\n";
    // tag::drop-primary-or-secondary-index[]
    // Drop a primary index
    $options = new DropQueryPrimaryIndexOptions();
    $options->scopeName("tenant_agent_01");
    $options->collectionName("users");

    $queryIndexMgr->dropPrimaryIndex("travel-sample", $options);

    // Drop a secondary index
    $options = new DropQueryIndexOptions();
    $options->scopeName("tenant_agent_01");
    $options->collectionName("users");

    $queryIndexMgr->dropIndex("travel-sample", "tenant_agent_01_users_email", $options);
    // end::drop-primary-or-secondary-index[]
}

main();
