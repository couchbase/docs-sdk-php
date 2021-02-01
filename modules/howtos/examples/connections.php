<?php

// #tag::rbac[]
$connectionString = "couchbase://localhost";
$opts = new \Couchbase\ClusterOptions();
$opts->credentials("Administrator", "password");
$cluster = new \Couchbase\Cluster($connectionString, $opts);
// #end::rbac[]
