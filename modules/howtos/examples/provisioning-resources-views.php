<?php

use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\DesignDocument;
use Couchbase\View;

$opts = new ClusterOptions();
$opts->credentials("Administrator", "password");

$cluster = new Cluster("localhost", $opts);
$bucket = $cluster->bucket("travel-sample");
$collection = $bucket->defaultCollection();

// tag::createViewMgr[]
$viewMgr = $bucket->viewIndexes();
// end::createViewMgr[]

try {
    // tag::createView[]
    $view1 = new View();
    $view1->setName("by_country");
    $view1->setMap("function (doc, meta) { if (doc.type == 'landmark') { emit([doc.country, doc.city], null); } }");
    $view1->setReduce("");

    $view2 = new View();
    $view2->setName("by_activity");
    $view2->setMap("function (doc, meta) { if (doc.type == 'landmark') { emit(doc.activity, null); } }");
    $view2->setReduce("_count");

    $designDoc = new DesignDocument();
    $designDoc->setName("landmarks");
    $designDoc->setViews([$view1->name() => $view1, $view2->name() => $view2]);

    $viewMgr->upsertDesignDocument($designDoc);
    sleep(1);
    // end::createView[]
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}

try {
    // tag::getView[]
    $doc = $viewMgr->getDesignDocument("landmarks");
    // end::getView[]
    printf("Design doc:", $doc->name());
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}

// try {
//     // tag::removeView[]
//     $viewMgr->dropDesignDocument("landmarks");
//     // end::removeView[]
// } catch (Exception $e) {
//     echo $e->getMessage();
//     exit(1);
// }
