<?php

function main() {
    $connectionString = "couchbase://localhost";
    $options = new \Couchbase\ClusterOptions();
    $options->credentials("Administrator", "password");
    $cluster = new \Couchbase\Cluster($connectionString, $options);

    $bucket = $cluster->bucket("travel-sample");


    print "scopeAdmin\n";
    // tag::scopeAdmin[]
    $users = $cluster->users();

    $user = new \Couchbase\User();
    $user->setUsername("scopeAdmin");
    $user->setDisplayName("Manage Collections in Scope [travel-sample:*]");
    $user->setPassword("password");
    $user->setRoles([
        (new \Couchbase\Role)->setName("scope_admin")->setBucket("travel-sample"),
        (new \Couchbase\Role)->setName("data_reader")->setBucket("travel-sample")
    ]);

    $users->upsertUser($user);
    // end::scopeAdmin[]

    print "create-collection-manager\n";

    // tag::create-collection-manager[]
    $options = new \Couchbase\ClusterOptions();
    $options->credentials("scopeAdmin", "password");
    $cluster = new \Couchbase\Cluster("localhost", $options);
    $bucket = $cluster->bucket("travel-sample");

    $collections = $bucket->collections();
    // end::create-collection-manager[]
    
    print "create-scope\n";
    // tag::create-scope[]
    try {
        $collections->createScope("example-scope");
    }
    catch (Exception $e) {
        print $e;
    }
    // end::create-scope[]

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

