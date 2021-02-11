<?php

use Couchbase\Cluster;
use Couchbase\ClusterOptions;

// tag::multiplenodes[]
$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");

$connectionString = "couchbase://10.112.210.101,10.112.210.102";
$cluster = new Cluster($connectionString, $opts);
// end::multiplenodes[]

// tag::customPorts[]
$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");

$connectionString = "couchbase://192.168.42.101:12000,192.168.42.102:12002";
$cluster = new Cluster($connectionString, $opts);
// end::customPorts[]

// tag::tls[]
$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");

$connectionString = "couchbases://localhost?truststorepath=/path/to/ca/certificates.pem";
$cluster = new Cluster($connectionString, $opts);
// end::tls[]

// tag::dnssrv[]
$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");

$connectionString = "couchbase://couchbase.example.org";
$cluster = new Cluster($connectionString, $opts);
// end::dnssrv[]
