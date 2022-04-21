<?php

require_once 'Couchbase/autoload.php';

use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;
use \Couchbase\TransactionsConfiguration;
use Couchbase\TransactionAttemptContext;
use Couchbase\TransactionQueryOptions;
use \Couchbase\DurabilityLevel;
use \Couchbase\QueryOptions;
use \Couchbase\QueryScanConsistency;
use \Couchbase\MutationState;

$CB_USER = getenv('CB_USER') ?: 'Administrator';
$CB_PASS = getenv('CB_PASS') ?: 'password';
$CB_HOST = getenv('CB_HOST') ?: 'couchbase://db';
$durabilityLevel = Couchbase\DurabilityLevel::NONE; // TODO

// tag::config[]
$options = new ClusterOptions();
$options->credentials($CB_USER, $CB_PASS);

$transactions_configuration = new TransactionsConfiguration();
$transactions_configuration->durabilityLevel($durabilityLevel); // Couchbase\DurabilityLevel::PERSIST_TO_MAJORITY etc.
$options->transactionsOptions($transactions_configuration);

$cluster = new Cluster($CB_HOST, $options);
// end::config[]

// tag::bucket[]
// get a reference to our bucket
$bucket = $cluster->bucket('travel-sample');
// end::bucket[]

// tag::collection[]
// get a reference to a collection
$collection = $bucket->scope('inventory')->collection('airline');
// end::collection[]
  
// tag::default-collection[]
// get a reference to the default collection, required for older Couchbase server versions
$collection_default = $bucket->defaultCollection();
// end::default-collection[]

function removeOrWarn($collection, $doc) {
  try {
    $collection->remove($doc);
  }
  catch (\Couchbase\Exception\DocumentNotFoundException $e) {
    echo "Document does not exist.\n";
  }
}

$testDoc = 'foo';
removeOrWarn($collection, $testDoc);
removeOrWarn($collection, 'doc-c');
removeOrWarn($collection, 'docId');
$collection->upsert('doc-a', []);
$collection->upsert('doc-b', []);


try {
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection, $testDoc) {
      $ctx->insert($collection, $testDoc, "hello");
    }
  );
}
catch (\Exception $e) {
  echo "Failed to insert: $e\n";
}

// tag::create[]
try {
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) {
    // `$ctx` is a TransactionAttemptContext which permits getting, inserting,
    // removing and replacing documents, performing N1QL queries, etc.
    // … Your transaction logic here …
    // Committing is implicit at the end of the lambda.
    });
}
catch (\Couchbase\Exception\TransactionOperationFailedException $e) {
    echo "Transaction did not reach commit point: $e\n";
}
catch (\Couchbase\Exception\TransactionException $e) { 
// TODO check is this equivalent to TransactionCommitAmbiguousError as per Node examples?
  echo "Transaction possibly committed: $e\n";
}
// end::create[]

// tag::examples[]

try {
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection) {
      // Inserting a doc:
      $ctx->insert($collection, 'doc-a', []);

      // Getting documents:
      $docA = $ctx->get($collection, 'doc-a');

      // Replacing a doc:
      $docB = $ctx->get($collection, 'doc-b');
      $content = $docB->content;
      $newContent = array_merge(
        ["transactions" => "are awesome"],
        $content);
      
      $ctx->replace($docB, $newContent);

      // Removing a doc:
      $docC = $ctx->get($collection, 'doc-c');
      $ctx->remove($docC);

      // Performing a SELECT N1QL query against a scope:
      $qr = $ctx->query('SELECT * FROM hotel WHERE country = $1',
        [ "scope" => "inventory",
          "parameters" => ["United Kingdom"]
      ]);
      echo $qr->rows();

      $ctx->query('UPDATE route SET airlineid = $1 WHERE airline = $2',
        [ "scope" => "inventory",
          "parameters" => ['airline_137', 'AF'] ] );
    });
}
catch (\Couchbase\Exception\TransactionOperationFailedException $e) {
    echo "Transaction did not reach commit point: $e\n";
}
catch (\Couchbase\Exception\TransactionException $e) { 
// TODO check is this equivalent to TransactionCommitAmbiguousError as per Node examples?
  echo "Transaction possibly committed: $e\n";
}
// end::examples[]

// execute other examples
getExample($cluster, $collection);
getReadOwnWritesExample($cluster, $collection);
replaceExample($cluster, $collection);
removeExample($cluster, $collection);
insertExample($cluster, $collection);
queryExamples($cluster, $collection);
queryInsert($cluster, $collection);
queryRYOW($cluster);
queryOptions($cluster);
querySingle($cluster);

# TODO set data up to run these examples fully
# playerHitsMonster(42, "arthur", "vogon", $cluster, $collection);
# rollback(...)
# rollbackCause(...)

function getExample($cluster, $collection) {
  echo "getExample\n";
  // tag::get[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection) { 
      $docA = $ctx->get($collection, "doc-a");
    });
  // end::get[]
  // TODO: should this show nullable/optional in an example?
}

function getReadOwnWritesExample($cluster, $collection) {
  // tag::getReadOwnWrites[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection) { 
    $docId = 'docId';
    $ctx->insert($collection, $docId, []);

    $doc = $ctx->get($collection, $docId);
  });
  // end::getReadOwnWrites[]
}

function replaceExample($cluster, $collection) {
  // tag::replace[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection) {
      $doc = $ctx->get($collection, "doc-b");
      $content = $doc->content();
      $newContent = array_merge(
        ["transactions" => "are awesome"],
        $content);
      
      $ctx->replace($doc, $newContent);
  });
  // end::replace[]
}

function removeExample($cluster, $collection) {
  // tag::remove[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection) {
      $doc = $ctx->get($collection, "docId");
      $ctx->remove($doc);
  });
  // end::remove[]
}

function insertExample($cluster, $collection) {
  // tag::insert[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection) { 
      $adoc = $ctx->insert($collection, "docId", []);
  });
  // end::insert[]
}

function queryExamples($cluster) {
  // tag::queryExamplesSelect[]
  $inventory = $cluster->bucket('travel-sample')->scope('inventory');

  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) { 
      $st = "SELECT * FROM `travel-sample`.inventory.hotel WHERE country = $1";
      $qr = $ctx->query(
        $st,
        TransactionQueryOptions::build()
          ->positionalParameters(["United Kingdom"]));

      foreach ($qr->rows() as $row) {
        // do something
      }
    }
  );
  // end::queryExamplesSelect[]

  // tag::queryExamplesUpdate[]
  $hotelChain = 'http://marriot%';
  $country = 'United States';
  
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($hotelChain, $country) { 
      
      // TODO: scope parameter doesn't work
      // $st = hotel SET price = $1 WHERE url LIKE $2 AND country = $3";
      $st = "UPDATE `travel-sample`.inventory.hotel SET price = $1 WHERE url LIKE $2 AND country = $3";
      $qr = $ctx->query(
        $st,
        TransactionQueryOptions::build()
          ->positionalParameters([99.99, $hotelChain, $country]));
          // ->scopeQualifier("`travel-sample`.`inventory`"));

      print_r($qr->metaData()->metrics());
      if ($qr->metaData()->metrics()["mutationCount"] != 2) {
        // throw new \Exception("Mutation count not the expected amount.");
        echo "WARN: Mutation count not the expected amount.\n";
      }
    }
  );
  // end::queryExamplesUpdate[]

  // tag::queryExamplesComplex[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($hotelChain, $country) { 
      // Find all hotels of the chain
      $qr = $ctx->query(
        'SELECT reviews FROM `travel-sample`.inventory.hotel WHERE url LIKE $1 AND country = $2',
        TransactionQueryOptions::build()
          ->positionalParameters([$hotelChain, $country]));
          // ->scopeQualifier("`travel-sample`.`inventory`"));

      // This function (not provided here) will use a trained machine learning model to provide a
      // suitable price based on recent customer reviews.
      function priceFromRecentReviews(Couchbase\QueryResult $qr) {
          // this would call a trained ML model to get the best price
          return 99.98;
      }
      $updatedPrice = priceFromRecentReviews($qr);

      // Set the price of all hotels in the chain
      $ctx->query(
        'UPDATE `travel-sample`.inventory.hotel SET price = $1 WHERE url LIKE $2 AND country = $3',
        TransactionQueryOptions::build()
          ->positionalParameters([$updatedPrice, $hotelChain, $country]));
          // ->scopeQualifier("`travel-sample`.`inventory`"));
    }
  );
  // end::queryExamplesComplex[]
}

function queryInsert($cluster) {
  // tag::queryInsert[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) { 
      $ctx->query("INSERT INTO `travel-sample`.inventory.airline VALUES ('doc-c', {'hello':'world'})"); // <1>
      $st = "SELECT `default`.* FROM `travel-sample`.inventory.airline WHERE META().id = 'doc-c'"; // <2>
      $qr = $ctx->query($st);
  });
  // end::queryInsert[]
}

function queryRYOW($cluster) {
  // tag::queryRYOW[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) {
      $qr = $ctx->query("UPDATE `travel-sample`.inventory.hotel SET price = 99.00 WHERE name LIKE \"Marriott%\"");
      if ($qr->metaData()->metrics()["mutationCount"] != 2) {
        // throw new \Exception("Mutation count not the expected amount.");
        echo "WARN: Mutation count not the expected amount.\n";
      }
    }
  );
// end::queryRYOW[]
}

function queryOptions($cluster) {
  // tag::queryOptions[]

  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) {
      $txQo = TransactionQueryOptions::build()
        ->timeout(1000)
        ->positionalParameters(["key", "value"]);
        
      $ctx->query(
        "UPSERT INTO `travel-sample`.inventory.airline VALUES ('docId', {\$1:\$2})",
        $txQo);
  });
  // end::queryOptions[]
}

function querySingle($cluster) {
  // tag::querySingle[]
  try {
    $cluster->transactions()->run(
      function (TransactionAttemptContext $ctx) {
        $bulkLoadStatement = "..."; // a bulk-loading N1QL statement not provided here

        $ctx->query($bulkLoadStatement);
    });
  }
  catch (\Couchbase\Exception\TransactionOperationFailedException $e) {
    echo "Transaction did not reach commit point\n";
  }
  catch (\Couchbase\Exception\TransactionException $e) { 
    echo "Transaction possibly committed\n";
  }
}
// end::querySingle[]

// SKIP querySingleScoped till checked Scoped examples above!
// async function querySingleScoped() {
//   let cluster = await getCluster()

//   const bulkLoadStatement = ""  /* your statement here */

//   // String bulkLoadStatement = null /* your statement here */;

//   // // tag::querySingleScoped[]
//   const travelSample = cluster.bucket("travel-sample")
//   const inventory = travelSample.scope("inventory")
//   // TODO: enable after implementation
//   // cluster.transactions().query(bulkLoadStatement, {scope: inventory})
//   // end::querySingleScoped[]
//   // Bucket travelSample = cluster.bucket("travel-sample");
//   // Scope inventory = travelSample.scope("inventory");
//   // transactions.query(inventory, bulkLoadStatement);
//   // // end::querySingleScoped[]
// }

// tag::full[]
function playerHitsMonster($damage, $playerId, $monsterId, $cluster, $collection) {
  try {
    $cluster->transactions()->run(
      function (TransactionAttemptContext $ctx) use ($damage, $playerId, $monsterId, $collection) {
        $monsterDoc = $ctx->get($collection, $monsterId);
        $monsterContent = $monsterDoc->content();
        $playerDoc = $ctx->get($playerId, $monsterId);
        $playerContent = $playerDoc->content();

        $monsterHitpoints = $monsterContent["hitpoints"];
        $monsterNewHitpoints = $monsterHitpoints - $damage;

        if ($monsterNewHitpoints <= 0) {
          // Monster is killed. The remove is just for demoing, and a more realistic
          // example would set a "dead" flag or similar.
          $ctx->remove($monsterDoc);

          // The player earns experience for killing the monster
          $experienceForKillingMonster = $monsterContent["experienceWhenKilled"];
          $playerExperience = $playerContent["experience"];
          $playerNewExperience = $playerExperience + $experienceForKillingMonster;
          $playerNewLevel =
            calculateLevelForExperience($playerNewExperience);

          $playerContent['experience'] = $playerNewExperience;
          $playerContent['level'] = $playerNewLevel;

          $ctx->replace($playerDoc, $playerContent);
      }
    });
  }
  catch (\Couchbase\Exception\TransactionOperationFailedException $e) {
    echo "Transaction did not reach commit point\n";
    // The operation failed. Both the monster and the player will be untouched.
    //
    // Situations that can cause this would include either the monster
    // or player not existing (as get is used), or a persistent
    // failure to be able to commit the transaction, for example on
    // prolonged node failure.
  }
  catch (\Couchbase\Exception\TransactionException $e) { 
    // TODO check is this equivalent to TransactionCommitAmbiguousError as per Node examples?
    // Indicates the state of a transaction ended as ambiguous and may or
    // may not have committed successfully.
    //
    // Situations that may cause this would include a network or node failure
    // after the transactions operations completed and committed, but before the
    // commit result was returned to the client
    echo "Transaction possibly committed\n";
  }
}
// end::full[]

function rollbackExample($cluster, $collection) {
  $costOfItem = 10;
  
  // tag::rollback[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection, $costOfItem) {
      $customer = $ctx->get($collection, "customer-name");
      if ($customer->content()["balance"] < $costOfItem) {
        throw new \Error("Transaction failed, customer does not have enough funds.");
      }
      // else continue transaction
  });
  // end::rollback[]
}

class InsufficientBalanceError extends Error {}

function rollbackCause($cluster, $collection) {
  $costOfItem = 10;

  // tag::rollback-cause[]
  try {
    $cluster->transactions()->run(
      function (TransactionAttemptContext $ctx) use ($collection, $costOfItem) {
        $customer = $ctx->get($collection, "customer-name");

        if ($customer->content()["balance"] < $costOfItem) {
          throw new \InsufficientBalanceError("Transaction failed, customer does not have enough funds.");
        }
        // else continue transaction
    });
  }
  catch (\Couchbase\Exception\TransactionOperationFailedException $e) {
    // This exception can only be thrown at the commit point, after the
    // BalanceInsufficient logic has been passed
  }
  catch (InsufficientBalanceError $e) {
    echo "user had insufficient balance";
  }
  // end::rollback-cause[]
}

function completeErrorHandling($cluster, $collection) {
  // tag::full-error-handling[]
  try {
    $result = $cluster->transactions()->run(
      function (TransactionAttemptContext $ctx) use ($collection, $costOfItem) {
        // ... transactional code here ...
      }
    );

    // The transaction definitely reached the commit point. Unstaging
    // the individual documents may or may not have completed

    if (! $result->unstagingComplete) {
        // In rare cases, the application may require the commit to have
        // completed.  (Recall that the asynchronous cleanup process is
        // still working to complete the commit.)
        // The next step is application-dependent.
    }
  }
  catch (\Couchbase\Exception\TransactionOperationFailedException $e) {
    echo "Transaction did not reach commit point\n";
  }
  catch (\Couchbase\Exception\TransactionException $e) { 
    echo "Transaction possibly committed\n";
  }
  // end::full-error-handling[]
}

echo "\n\nTransactions Examples completed successfully\n\n";
