<?php

/**
 * @file
 * Aws-secrets.php.
 */

require 'vendor/autoload.php';

use Aws\Exception\AwsException;
use Aws\SecretsManager\SecretsManagerClient;

/**
 *
 */
function get_secret($secretName, $region) {
  $client = new SecretsManagerClient([
    'version' => 'latest',
    'region' => $region,
  ]);

  try {
    $result = $client->getSecretValue([
      'SecretId' => $secretName,
    ]);

    if (isset($result['SecretString'])) {
      return json_decode($result['SecretString'], TRUE);
    }
    else {
      return json_decode(base64_decode($result['SecretBinary']), TRUE);
    }
  }
  catch (AwsException $e) {
    error_log($e->getMessage());
    return NULL;
  }
}
