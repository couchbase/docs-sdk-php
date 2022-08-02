<?php

require_once '../../../vendor/autoload.php';

use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\Management\CreateQueryPrimaryIndexOptions;
use Couchbase\Management\Role;
use Couchbase\Management\User;
use Couchbase\RawJsonTranscoder;

function main()
{
    $options = new ClusterOptions();
    $options->credentials("Administrator", "password");
    $adminCluster = new Cluster("couchbase://localhost", $options);

    $userMgr = $adminCluster->users();

    createUser($userMgr);
    sleep(2); // Give user creation some time to settle before getAllUsers() call.
    getAllUsers($userMgr);
    userOperations();
}

function createUser($userMgr)
{
    // tag::create-user[]
    $roles = [
        // Roles required for reading data from bucket
        Role::build()->setName("data_reader")->setBucket("*"),
        Role::build()->setName("query_select")->setBucket("*"),
        // Roles required for writing data to bucket
        Role::build()->setName("data_writer")->setBucket("travel-sample"),
        Role::build()->setName("query_insert")->setBucket("travel-sample"),
        Role::build()->setName("query_delete")->setBucket("travel-sample"),
        // Roles required for idx creation on bucket
        Role::build()->setName("query_manage_index")->setBucket("travel-sample"),
    ];

    $user = new User();
    $user->setUsername("test-user");
    $user->setDisplayName("Test User");
    $user->setRoles($roles);
    $user->setPassword("test-passw0rd!");

    $userMgr->upsertUser($user);
    // end::create-user[]
}

function getAllUsers($userMgr)
{
    // tag::get-all-users[]
    $userMetadata = $userMgr->getAllUsers();
    foreach ($userMetadata as &$u) {
        printf("User's display name: %s\n", $u->user()->displayName());

        $userRoles = $u->user()->roles();
        foreach ($userRoles as &$role) {
            printf("\tUser has role %s, applicable to bucket %s\n", $role->name(), $role->bucket());
        }
    }
    // end::get-all-users[]
}

function userOperations()
{
    # tag::user-operations[]
    $options = new ClusterOptions();
    $options->credentials("test-user", "test-passw0rd!");
    $userCluster = new Cluster("couchbase://localhost", $options);

    # For Server versions 6.5 or later you do not need to open a bucket here
    $userBucket = $userCluster->bucket("travel-sample");
    $collection = $userBucket->defaultCollection();

    # create primary idx for testing purposes
    $createPrimaryQueryIndexOpts = new CreateQueryPrimaryIndexOptions();
    $createPrimaryQueryIndexOpts->ignoreIfExists(true);

    $userCluster->queryIndexes()->createPrimaryIndex("travel-sample", $createPrimaryQueryIndexOpts);

    # test k/v operations
    $airline10 = $collection->get("airline_10");
    printf("Airline 10: %s", $airline10->contentAs(RawJsonTranscoder::getInstance()));

    $airline11 = [
        "callsign" => "MILE-AIR",
        "iata" => "Q5", "id" => 11,
        "name" => "40-Mile Air",
        "type" => "airline"
    ];
    $collection->upsert("airline11", $airline11);

    # test query operations
    $queryResult = $userCluster->query("SELECT * FROM `travel-sample` LIMIT 5;");
    foreach ($queryResult->rows() as &$row) {
        print("\nQuery Row:\n");
        print_r($row);
    }
    # end::user-operations[]
}

main();
