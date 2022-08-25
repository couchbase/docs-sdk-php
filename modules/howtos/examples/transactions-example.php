<?php

require_once '../../../vendor/autoload.php';

use Couchbase\ClusterOptions;
use Couchbase\Cluster;
use Couchbase\TransactionsConfiguration;
use Couchbase\TransactionAttemptContext;
use Couchbase\TransactionQueryOptions;
use Couchbase\DurabilityLevel;
use Couchbase\QueryOptions;
use Couchbase\QueryScanConsistency;
use Couchbase\MutationState;

// tag::config[]
$CB_USER = getenv('CB_USER') ?: 'Administrator';
$CB_PASS = getenv('CB_PASS') ?: 'password';
$CB_HOST = getenv('CB_HOST') ?: 'couchbase://localhost';

$options = new ClusterOptions();
$options->credentials($CB_USER, $CB_PASS);

$transactions_configuration = new TransactionsConfiguration();
$transactions_configuration->durabilityLevel(Couchbase\DurabilityLevel::PERSIST_TO_MAJORITY);
$options->transactionsConfiguration($transactions_configuration);

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

function removeOrWarn($collection, $doc)
{
  try {
    $collection->remove($doc);
  } catch (\Couchbase\Exception\DocumentNotFoundException $e) {
    echo "Document does not exist.\n";
  }
}

$testDoc = 'foo';
removeOrWarn($collection, $testDoc);
removeOrWarn($collection, 'doc-c');
removeOrWarn($collection, 'docId');
removeOrWarn($collection, 'doc-greeting');
$collection->upsert('doc-a', []);
$collection->upsert('doc-b', []);
$collection->upsert('customer-name', ['balance' => 9]);

try {
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection, $testDoc) {
      $ctx->insert($collection, $testDoc, "hello");
    }
  );
} catch (\Couchbase\Exception\TransactionFailedException $e) {
  echo "Transaction did not reach commit point: $e\n";
} catch (\Couchbase\Exception\TransactionCommitAmbiguousException $e) {
  echo "Transaction possibly committed: $e\n";
}

echo "Running: create example\n";
// tag::create[]
try {
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) {
      // `$ctx` is a TransactionAttemptContext which permits getting, inserting,
      // removing and replacing documents, performing N1QL queries, etc.
      // … Your transaction logic here …
      // Committing is implicit at the end of the lambda.
    }
  );
} catch (\Couchbase\Exception\TransactionFailedException $e) {
  echo "Transaction did not reach commit point: $e\n";
} catch (\Couchbase\Exception\TransactionCommitAmbiguousException $e) {
  echo "Transaction possibly committed: $e\n";
}
// end::create[]

echo "Running: create-simple example\n";
// tag::create-simple[]
$cluster->transactions()->run(
  function (TransactionAttemptContext $ctx) use ($collection) {
    $ctx->insert($collection, 'doc1', ['hello' => 'world']);

    $doc = $ctx->get($collection, 'doc1');
    $ctx->replace($doc, ['foo' => 'bar']);
  }
);
// end::create-simple[]

echo "\nRunning: examples\n";
// tag::examples[]
try {
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection) {
      // Inserting a doc:
      $ctx->insert($collection, 'doc-c', []);

      // Getting documents:
      $docA = $ctx->get($collection, 'doc-a');

      // Replacing a doc:
      $docB = $ctx->get($collection, 'doc-b');
      $content = $docB->content();
      $newContent = array_merge(
        ["transactions" => "are awesome"],
        $content
      );
      $ctx->replace($docB, $newContent);

      // Removing a doc:
      $docC = $ctx->get($collection, 'doc-c');
      $ctx->remove($docC);

      // Performing a SELECT N1QL query:
      $selectQuery = 'SELECT * FROM `travel-sample`.inventory.hotel WHERE country = $1 LIMIT 5';
      $qr = $ctx->query(
        $selectQuery,
        TransactionQueryOptions::build()
          ->positionalParameters(["United Kingdom"])
      );
      foreach ($qr->rows() as $row) {
        printf("Name: %s, Country: %s\n", $row["hotel"]["name"], $row["hotel"]["country"]);
      }

      // Performing an UPDATE N1QL query:
      $updateQuery = 'UPDATE `travel-sample`.inventory.route SET airlineid = $1 WHERE airline = $2 LIMIT 5';
      $ctx->query(
        $updateQuery,
        TransactionQueryOptions::build()
          ->positionalParameters(['airline_137', 'AF'])
      );
    }
  );
} catch (\Couchbase\Exception\TransactionFailedException $e) {
  echo "Transaction did not reach commit point: $e\n";
} catch (\Couchbase\Exception\TransactionCommitAmbiguousException $e) {
  echo "Transaction possibly committed: $e\n";
}
// end::examples[]

// execute other examples
getExample($cluster, $collection);
getReadOwnWritesExample($cluster, $collection);
replaceExample($cluster, $collection);
removeExample($cluster, $collection);
insertExample($cluster, $collection);
queryExamples($cluster);
queryRYOW($cluster);
queryKvMix($cluster, $collection);
queryOptions($cluster);
// TODO: Uncomment when we have clarity on what the example should do.
// querySingle($cluster);

// Rollback
playerHitsMonster(42, "arthur", "vogon", $cluster, $collection);
// rollbackExample($cluster, $collection);
rollbackCause($cluster, $collection);

function getExample($cluster, $collection)
{
  echo "\nRunning: get example\n";
  // tag::get[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection) {
      $docA = $ctx->get($collection, "doc-a");
    }
  );
  // end::get[]
  // TODO: should this show nullable/optional in an example?
}

function getReadOwnWritesExample($cluster, $collection)
{
  echo "\nRunning: getReadOwnWrites example\n";
  // tag::getReadOwnWrites[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection) {
      $docId = 'docId';
      $ctx->insert($collection, $docId, []);

      $doc = $ctx->get($collection, $docId);
    }
  );
  // end::getReadOwnWrites[]
}

function replaceExample($cluster, $collection)
{
  echo "\nRunning: replace example\n";
  // tag::replace[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection) {
      $doc = $ctx->get($collection, "doc-b");
      $content = $doc->content();
      $newContent = array_merge(
        ["transactions" => "are awesome"],
        $content
      );

      $ctx->replace($doc, $newContent);
    }
  );
  // end::replace[]
}

function removeExample($cluster, $collection)
{
  echo "\nRunning: remove example\n";
  // tag::remove[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection) {
      $doc = $ctx->get($collection, "docId");
      $ctx->remove($doc);
    }
  );
  // end::remove[]
}

function insertExample($cluster, $collection)
{
  echo "\nRunning: insert example\n";
  // tag::insert[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection) {
      $adoc = $ctx->insert($collection, "docId", []);
    }
  );
  // end::insert[]
}

function queryExamples($cluster)
{
  echo "\nRunning: queryExamplesSelect\n";
  // TODO: scopeQualifier/scopeName options are both deprecated.
  // https://github.com/couchbase/couchbase-php-client/blob/4.0.0/Couchbase/TransactionQueryOptions.php#L296-L325
  // It's unclear what we should use to demonstrate scope level queries.
  // Using full qualifier for now.
  // tag::queryExamplesSelect[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) {
      $st = "SELECT * FROM `travel-sample`.inventory.hotel WHERE country = $1";
      $qr = $ctx->query(
        $st,
        TransactionQueryOptions::build()
          ->positionalParameters(["United Kingdom"])
      );

      foreach ($qr->rows() as $row) {
        // do something
      }
    }
  );
  // end::queryExamplesSelect[]

  // TODO: scopeQualifier/scopeName options are both deprecated.
  // https://github.com/couchbase/couchbase-php-client/blob/4.0.0/Couchbase/TransactionQueryOptions.php#L296-L325
  // It's unclear what we should use to demonstrate scope level queries.
  // Using full qualifier for now.
  // $st = UPDATE hotel SET price = $1 WHERE url LIKE $2 AND country = $3";
  // tag::queryExamplesUpdate[]
  $hotelChain = 'http://marriot%';
  $country = 'United States';

  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($hotelChain, $country) {
      $st = "UPDATE `travel-sample`.inventory.hotel SET price = $1 WHERE url LIKE $2 AND country = $3";
      $qr = $ctx->query(
        $st,
        TransactionQueryOptions::build()
          ->positionalParameters([99.99, $hotelChain, $country])
      );

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
          ->positionalParameters([$hotelChain, $country])
      );

      // This function (not provided here) will use a trained machine learning model to provide a
      // suitable price based on recent customer reviews.
      function priceFromRecentReviews(Couchbase\QueryResult $qr)
      {
        // this would call a trained ML model to get the best price
        return 99.98;
      }
      $updatedPrice = priceFromRecentReviews($qr);

      // Set the price of all hotels in the chain
      $ctx->query(
        'UPDATE `travel-sample`.inventory.hotel SET price = $1 WHERE url LIKE $2 AND country = $3',
        TransactionQueryOptions::build()
          ->positionalParameters([$updatedPrice, $hotelChain, $country])
      );
    }
  );
  // end::queryExamplesComplex[]
}

function queryRYOW($cluster)
{
  echo "\nRunning: queryInsert example\n";
  // tag::queryRYOW[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) {
      // Query INSERT
      $ctx->query(
        "INSERT INTO `travel-sample`.inventory.airline VALUES ('doc-c', {'hello':'world'})" // <1>
      );

      // Query SELECT
      $ctx->query(
        "SELECT hello FROM `travel-sample`.inventory.airline WHERE META().id = 'doc-c'" // <2>
      );
    }
  );
  // end::queryRYOW[]
}

function queryKvMix($cluster, $collection)
{
  echo "\nRunning: queryKvMix example\n";
  // tag::queryKvMix[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) use ($collection) {
      // Key-Value insert
      $ctx->insert($collection, "doc-greeting", ["greeting" => "Hello World"]); // <1>

      // Query SELECT 
      $selectQuery = "SELECT greeting FROM `travel-sample`.inventory.airline WHERE META().id = 'doc-greeting'";
      $ctx->query($selectQuery); // <2>
    }
  );
  // end::queryKvMix[]
}

function queryOptions($cluster)
{
  echo "\nRunning: queryOptions example\n";
  // tag::queryOptions[]
  $cluster->transactions()->run(
    function (TransactionAttemptContext $ctx) {
      $txQo = TransactionQueryOptions::build()
        ->readonly(false)
        ->positionalParameters(["key", "value"]);

      $ctx->query(
        "UPSERT INTO `travel-sample`.inventory.airline VALUES ('docId', {\$1:\$2})",
        $txQo
      );
    }
  );
  // end::queryOptions[]
}

// TODO: Verify this is the correct example for a single query transaction. 
// function querySingle($cluster)
// {
//   echo "\nRunning: querySingle example\n";
//   // tag::querySingle[]
//   try {
//     $cluster->transactions()->run(
//       function (TransactionAttemptContext $ctx) {
//         $bulkLoadStatement = "..."; // a bulk-loading N1QL statement not provided here

//         $ctx->query($bulkLoadStatement);
//       }
//     );
//   } catch (\Couchbase\Exception\TransactionFailedException $e) {
//     echo "Transaction did not reach commit point\n";
//   } catch (\Couchbase\Exception\TransactionCommitAmbiguousException $e) {
//     echo "Transaction possibly committed\n";
//   }
//   // end::querySingle[]
// }

// SKIP querySingleScoped till checked Scoped examples above!
// async function querySingleScoped() {
//   let cluster = await getCluster()

//   const bulkLoadStatement = ""  /* your statement here */

//   // String bulkLoadStatement = null /* your statement here */;

//   // // tag::querySingleScoped[]
//   const travelSample = cluster.bucket("travel-sample")
//   const inventory = travelSample.scope("inventory")
//   // TODO: enable after implementation
//   // cluster.query(bulkLoadStatement, queryOptions().asTransaction())
//   // // end::querySingleScoped[]
// }

echo "\nRunning: full example\n";
// tag::full[]
function playerHitsMonster($damage, $playerId, $monsterId, $cluster, $collection)
{
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
          $playerNewLevel = calculateLevelForExperience($playerNewExperience);

          $playerContent['experience'] = $playerNewExperience;
          $playerContent['level'] = $playerNewLevel;

          $ctx->replace($playerDoc, $playerContent);
        }
      }
    );
  } catch (\Couchbase\Exception\TransactionFailedException $e) {
    echo "Transaction did not reach commit point\n";
    // The operation failed. Both the monster and the player will be untouched.
    //
    // Situations that can cause this would include either the monster
    // or player not existing (as get is used), or a persistent
    // failure to be able to commit the transaction, for example on
    // prolonged node failure.
  } catch (\Couchbase\Exception\TransactionCommitAmbiguousException $e) {
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

class InsufficientBalanceException extends Exception
{
}

function rollbackCause($cluster, $collection)
{
  echo "\nRunning: rollback-cause example\n";
  // tag::rollback-cause[]
  $costOfItem = 10;

  try {
    $cluster->transactions()->run(
      function (TransactionAttemptContext $ctx) use ($collection, $costOfItem) {
        $customer = $ctx->get($collection, "customer-name");

        if ($customer->content()["balance"] < $costOfItem) {
          throw new \InsufficientBalanceException("Transaction failed, customer does not have enough funds.");
        }
        // else continue transaction
      }
    );
  } catch (\Couchbase\Exception\TransactionFailedException $e) {
    echo "Transaction did not reach commit point: $e\n";
  } catch (\Couchbase\Exception\TransactionCommitAmbiguousException $e) {
    echo "Transaction possibly committed: $e\n";
  }
  // end::rollback-cause[]
}

echo "\nRunning: full-error-handling example\n";
function completeErrorHandling($cluster, $collection)
{
  // tag::full-error-handling[]
  try {
    $result = $cluster->transactions()->run(
      function (TransactionAttemptContext $ctx) use ($collection, $costOfItem) {
        // ... transactional code here ...
      }
    );

    // The transaction definitely reached the commit point. Unstaging
    // the individual documents may or may not have completed

    if (!$result->unstagingComplete) {
      // In rare cases, the application may require the commit to have
      // completed.  (Recall that the asynchronous cleanup process is
      // still working to complete the commit.)
      // The next step is application-dependent.
    }
  } catch (\Couchbase\Exception\TransactionFailedException $e) {
    echo "Transaction did not reach commit point\n";
  } catch (\Couchbase\Exception\TransactionCommitAmbiguousException $e) {
    echo "Transaction possibly committed\n";
  }
  // end::full-error-handling[]
}

echo "\n\nTransactions Examples completed successfully\n\n";
