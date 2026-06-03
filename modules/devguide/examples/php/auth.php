<?php

use Couchbase\ClusterOptions;
use Couchbase\Cluster;

// tag::auth1[]
$options = new ClusterOptions();
$options->credentials("Administrator", "password");
$cluster = new Cluster("couchbase://localhost", $options);
$bucket = $cluster->bucket("travel-sample");
// end::auth1[]

// tag::auth2[]
$options = new ClusterOptions();
$options->credentials("Administrator", "password");

# authentication with TLS client certificate
$connectionString = "couchbases://localhost?" .
    "truststorepath=/path/to/ca/certificates.pem&" .
    "certpath=/path/to/client/certificate.pem&" .
    "keypath=/path/to/client/key.pem";

$cluster = new Cluster($connectionString, $options);
$bucket = $cluster->bucket("travel-sample");
// end::auth2[]

// tag::auth3[]
$options = new ClusterOptions();
$options->credentials("Administrator", "password");
$cluster = new Cluster("couchbase://localhost?sasl_mech_force=PLAIN", $options);
$bucket = $cluster->bucket("travel-sample");
// end::auth3[]
