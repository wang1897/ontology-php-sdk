<?php

use PHPUnit\Framework\TestCase;
use ontio\network\JsonRpc;
use ontio\crypto\PrivateKey;
use ontio\crypto\PublicKey;
use ontio\sdk\Account;
use ontio\crypto\Address;
use ontio\sdk\Wallet;
use ontio\smartcontract\nativevm\OntidContractTxBuilder;
use ontio\core\transaction\TransactionBuilder;
use ontio\network\WebsocketRpc;

final class JsonRpcTest extends TestCase
{
  /** @var JsonRpc */
  public static $rpc;

  /** @var PrivateKey */
  public static $priKey;

  /** @var PublicKey */
  public static $pubKey;

  /** @var Account */
  public static $account;

  /** @var Address */
  public static $address;

  /** @var Wallet */
  public static $wallet;

  /** @var PrivateKey */
  public static $adminPrivateKey;

  /** @var Address */
  public static $adminAddress;

  /** @var string */
  public static $gasLimit;

  /** @var string */
  public static $gasPrice;

  public static $ontid;

  public static $txHash;

  public static function setUpBeforeClass()
  {
    self::$rpc = new JsonRpc('http://127.0.0.1:20336');
    self::$priKey = PrivateKey::random();
    self::$pubKey = self::$priKey->getPublicKey();
    self::$account = Account::create('123456', self::$priKey);
    self::$address = self::$account->address;

    $walletData = '{"name":"MyWallet","version":"1.1","scrypt":{"p":8,"n":16384,"r":8,"dkLen":64},"accounts":[{"address":"ASSxYHNSsh4FdF2iNvHdh3Np2sgWU21hfp","enc-alg":"aes-256-gcm","key":"t2Kk2jNL4BAoXlYn309DKfxogxJRNvsJ8+GG4kiMB+UvWGXEilYRzfIYeNZbfVbu","algorithm":"ECDSA","salt":"CdRa1hTiOaVESNfJJmcMNw==","parameters":{"curve":"P-256"},"label":"","publicKey":"0344ea636caaebf23c7cec2219a75bd6260f891413467922975447ba57f3c824c6","signatureScheme":"SHA256withECDSA","isDefault":true,"lock":false},{"address":"AL9PtS6F8nue5MwxhzXCKaTpRb3yhtsix5","enc-alg":"aes-256-gcm","key":"vwIgX3qJO+1XikdPAfjAu/clsgS2l2xkEWsRR9XZQ8OyFViX+r/6Yq+cV0wnKQUM","algorithm":"SM2","salt":"xzvrFkHAgsEeX64V+4mpLw==","parameters":{"curve":"sm2p256v1"},"label":"","publicKey":"131403a9b89a0443ded240c3dee97221353d000d0dc905b7c085f4ef558b234a75e122","signatureScheme":"SM3withSM2","isDefault":false,"lock":false}]}';
    self::$wallet = Wallet::fromJson($walletData);
    self::$adminPrivateKey = self::$wallet->accounts[0]->exportPrivateKey('123456', self::$wallet->scrypt);
    self::$adminAddress = self::$wallet->accounts[0]->address;
    self::$gasLimit = '20000';
    self::$gasPrice = '0';

    self::$ontid = 'did:ont:' . self::$address->toBase58();

    $ontIdBuilder = new OntidContractTxBuilder();
    $tx = $ontIdBuilder->buildRegisterOntidTx(
      self::$ontid,
      self::$adminPrivateKey->getPublicKey(),
      self::$gasPrice,
      self::$gasLimit,
      self::$adminAddress
    );

    $txBuilder = new TransactionBuilder();
    $txBuilder->signTransaction($tx, self::$adminPrivateKey);

    $wsRpc = new WebsocketRpc('ws://127.0.0.1:20335');
    $res = $wsRpc->sendRawTransaction($tx->serialize(), false, true);
    self::$txHash = $res->Result->TxHash;
  }

  public function test_block_height()
  {
    $h = self::$rpc->getBlockHeight();
    $this->assertEquals('SUCCESS', $h->desc);
  }

  public function test_get_balance()
  {
    $b = self::$rpc->getBalance(self::$address);
    $this->assertEquals('SUCCESS', $b->desc);
  }

  public function test_get_node_count()
  {
    $c = self::$rpc->getNodeCount();
    $this->assertEquals('SUCCESS', $c->desc);
  }

  public function test_get_unclaimed_ong()
  {
    $c = self::$rpc->getUnclaimedOng(new Address('ASSxYHNSsh4FdF2iNvHdh3Np2sgWU21hfp'));
    $this->assertEquals('SUCCESS', $c->desc);
  }

  public function test_get_smart_code_event()
  {
    $c = self::$rpc->getSmartCodeEvent(self::$txHash);
    $this->assertEquals('SUCCESS', $c->desc);
    $this->assertEquals(self::$txHash, $c->result->TxHash);
  }
}
