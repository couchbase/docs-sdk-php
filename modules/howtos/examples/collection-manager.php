<?php

use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;
use \Couchbase\User;

function getCollections($username, $password) {
    print "create-collection-manager\n";

	// tag::create-collection-manager[]
    $options = new \Couchbase\ClusterOptions();
    $options->credentials($username, $password);
    $cluster = new \Couchbase\Cluster("localhost", $options);
    $bucket = $cluster->bucket("travel-sample");

    $collections = $bucket->collections();
	// end::create-collection-manager[]

	return $collections;
}

function main() {
    $connectionString = "couchbase://localhost";
    $options = new \Couchbase\ClusterOptions();
    $options->credentials("Administrator", "password");
    $cluster = new \Couchbase\Cluster($connectionString, $options);

    $bucket = $cluster->bucket("travel-sample");

    $users = $cluster->users();

    print "bucketAdmin\n";
    // tag::bucketAdmin[]
    $user = new \Couchbase\User();
    $user->setUsername("bucketAdmin");
    $user->setDisplayName("Bucket Admin [travel-sample]");
    $user->setPassword("password");
    $user->setRoles([
        (new \Couchbase\Role)->setName("bucket_admin")->setBucket("travel-sample")
    ]);

    $users->upsertUser($user);
    // end::bucketAdmin[]

    $collections = getCollections("bucketAdmin", "password");

    print "create-scope\n";
    // tag::create-scope[]
    try {
        $collections->createScope("example-scope");
    }
    catch (Exception $e) {
        print $e;
    }
    // end::create-scope[]

    print "scopeAdmin\n";
	// tag::scopeAdmin[]

    $user = new \Couchbase\User();
    $user->setUsername("scopeAdmin");
    $user->setDisplayName("Manage Collections in Scope [travel-sample:*]");
    $user->setPassword("password");
    $user->setRoles([
        (new \Couchbase\Role)->setName("scope_admin")->setBucket("travel-sample")->setScope("example-scope"),
        (new \Couchbase\Role)->setName("data_reader")->setBucket("travel-sample")
    ]);

    $users->upsertUser($user);
	// end::scopeAdmin[]

    $collections = getCollections("scopeAdmin", "password");

    print "create-collection\n";
    // tag::create-collection[]
    $collection = new \Couchbase\CollectionSpec();
    $collection->setName("example-collection");
    $collection->setScopeName("example-scope");

    try {
        $collections->createCollection($collection);
    }
    catch (Exception $e) {
        print $e;
    }
    // end::create-collection[]

    print "drop-collection\n";
    // tag::drop-collection[]
    $collections->dropCollection($collection);
    // end::drop-collection[]

    print "drop-scope\n";
    // tag::drop-scope[]
    $collections->dropScope("example-scope");
    // end::drop-scope[]

}

main();

