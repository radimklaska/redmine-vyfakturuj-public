<?php

declare(strict_types=1);

// src/Command/RVCommand.php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VyfakturujAPI;

final class RVCommand extends Command {

  private $RedmineDomain;

  private $RedmineApiKey;

  private $RedmineUserId;

  private $HourlyRate;

  /**
   * In this method setup command, description, and its parameters
   */
  protected function configure() {
    $this->setName('sync');
    $this->setDescription('Sync redmine to vyfakturuj.cz.');
    $this->addArgument('RedmineDomain', InputArgument::REQUIRED, 'Redmine domain.');
    $this->addArgument('RedmineApiKey', InputArgument::REQUIRED, 'Redmine User API key.');
    $this->addArgument('RedmineUserId', InputArgument::REQUIRED, 'Redmine User ID.');
    $this->addArgument('HourlyRate', InputArgument::REQUIRED, 'Hourly rate in CZK.');
    $this->addArgument('VyfakturujLogin', InputArgument::REQUIRED, 'Vyfakturuj.cz username.');
    $this->addArgument('VyfakturujApi', InputArgument::REQUIRED, 'API key. https://app.vyfakturuj.cz/nastaveni/api');
  }

  /**
   * Here all logic happens
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->RedmineDomain = $input->getArgument('RedmineDomain');
    $this->RedmineApiKey = $input->getArgument('RedmineApiKey');
    $this->RedmineUserId = $input->getArgument('RedmineUserId');
    $this->HourlyRate = $input->getArgument('HourlyRate');
    $this->VyfakturujLogin = $input->getArgument('VyfakturujLogin');
    $this->VyfakturujApi = $input->getArgument('VyfakturujApi');

    $first_day_of_last_month = date("Y-m-d", strtotime("first day of last month"));
    $last_day_of_last_month = date("Y-m-d", strtotime("last day of last month"));
    $first_day_of_this_month = date("Y-m-d", strtotime("first day of this month"));
    $last_day_of_this_month = date("Y-m-d", strtotime("last day of this month"));
    $number_of_days_in_this_month = date("d", strtotime("last day of this month"));
    $days_due = $number_of_days_in_this_month - 1;

    $entries = $this->redmine_get_entries($first_day_of_last_month, $last_day_of_last_month);

    $headerTimeEntries = [
      //        'ID',
      //        'Project ID',
      //        'Project name',
      'Issue number',
      //        'User ID',
      //        'User name',
      //        'Activity ID',
      'Activity name',
      'Hours',
      'Comments',
      'Spent on',
    ];
    $rowsTimeEntries = [];
    $total_time_spent = 0;
    foreach ($entries as $index => $entry) {
      $total_time_spent = $total_time_spent + $entry['hours'];
      $rowsTimeEntries[] = [
        //        $entry['id'],
        //        $entry['project']['id'],
        //        $entry['project']['name'],
        $entry['issue']['id'],
        //        $entry['user']['id'],
        //        $entry['user']['name'],
        //        $entry['activity']['id'],
        $entry['activity']['name'],
        $entry['hours'],
        substr($entry['comments'], 0, 70),
        $entry['spent_on'],
      ];
    }

    if ($output->isVerbose()) {
      $tableTimeEntries = new Table($output);
      $tableTimeEntries
        ->setHeaders($headerTimeEntries)
        ->setRows($rowsTimeEntries);
      $tableTimeEntries->render();
    }

    $output->writeln(sprintf(
      'The range is from %s to %s', $first_day_of_last_month, $last_day_of_last_month
    ));
    $output->writeln(sprintf(
      'Total hours: %s', $total_time_spent
    ));
    $output->writeln(sprintf(
      'That\'s: %s CZK', number_format(($total_time_spent * $this->HourlyRate), 0, ",", " ")
    ));


    $vyfakturuj_api = new VyfakturujAPI($this->VyfakturujLogin, $this->VyfakturujApi);
    //    $vyfakturuj_api->setEndpointUrl('https://private-anon-54a78d1ef5-vyfakturujcz.apiary-mock.com/2.0/');

    // Calculate per project
    $headerProjectEntries = [
      'quantity',
      'unit',
      'text',
      'unit_price',
      'vat_rate',
    ];
    $invoceItems = [];
    foreach ($entries as $index => $entry) {
      $invoceItems[$entry['project']['id']] = [
        'quantity' => ((isset($invoceItems[$entry['project']['id']]['quantity'])) ? $invoceItems[$entry['project']['id']]['quantity'] : 0) + $entry['hours'],
        'unit' => 'h',
        'text' => $entry['project']['name'],
        'unit_price' => $this->HourlyRate,
        'vat_rate' => 0,
      ];
    }

    // Sort by Project name.
    usort($invoceItems, function($a, $b) {
      return $a['text'] <=> $b['text'];
    });

    $tableProjectEntries = new Table($output);
    $tableProjectEntries
      ->setHeaders($headerProjectEntries)
      ->setRows($invoceItems);
    $tableProjectEntries->render();

    // @ToDo: Provide a way to override the invoice data.
    $newInvoice = [
      'id_customer' => 'FILL-ME',
      'id_payment_method' => 'FILL-ME',
      'type' => '1',
      'date_created' => $first_day_of_this_month,
      'date_due' => $last_day_of_this_month,
      'days_due' => $days_due,
      'supplier_IC' => 'FILL-ME',
      'supplier_DIC' => '',
      'supplier_IDNUM3' => '',
      'supplier_name' => 'FILL-ME',
      'supplier_street' => 'FILL-ME',
      'supplier_city' => 'FILL-ME',
      'supplier_zip' => 'FILL-ME',
      'supplier_country' => 'FILL-ME',
      'supplier_country_code' => 'FILL-ME',
      'supplier_contact_name' => 'FILL-ME',
      'supplier_contact_tel' => 'FILL-ME',
      'supplier_contact_mail' => 'FILL-ME',
      'supplier_contact_web' => 'FILL-ME',
      'customer_IC' => 'FILL-ME',
      'customer_DIC' => '',
      'customer_IDNUM3' => '',
      'customer_name' => 'FILL-ME',
      'customer_firstname' => '',
      'customer_lastname' => '',
      'customer_street' => 'FILL-ME',
      'customer_city' => 'FILL-ME',
      'customer_zip' => 'FILL-ME',
      'customer_country' => 'FILL-ME',
      'customer_country_code' => 'FILL-ME',
      'customer_tel' => '',
      'customer_delivery_company' => '',
      'customer_delivery_firstname' => '',
      'customer_delivery_lastname' => '',
      'customer_delivery_street' => '',
      'customer_delivery_city' => '',
      'customer_delivery_zip' => '',
      'customer_delivery_country' => 'Czech Republic',
      'customer_delivery_country_code' => 'CZ',
      'customer_delivery_tel' => '',
      'bank_account_number' => 'FILL-ME',
      'bank_IBAN' => 'FILL-ME',
      'bank_BIC' => 'FILL-ME',
      'payment_method' => 'FILL-ME',
      'calculate_vat' => 'FILL-ME',
      'round_invoice' => 'FILL-ME',
      'text_invoice_footer' => 'Total time: ' . $total_time_spent . 'h',
      'language' => 'en',
      'currency' => 'CZK',
      'currency_domestic' => 'CZK',
      'items' => $invoceItems,
    ];

    $invoice = $vyfakturuj_api->createInvoice($newInvoice);

    if ($output->isVerbose()) {
      $output->writeln(sprintf(
        'Vyfakturuj response: %s', json_encode($invoice, JSON_PRETTY_PRINT)
      ));
    }

    $output->writeln(sprintf(
      'Vyfakturuj URL: %s', $invoice['url_app_detail']
    ));

    // return value is important when using CI, to fail the build when the command fails
    // in case of fail: "return self::FAILURE;"
    return self::SUCCESS;
  }


  protected function redmine_get_entries($start, $end) {
    $pager = 100; // no more than 100, redmine forces 100 if you try more.
    $result = $this->redmine_get_time_entries($start, $end, $pager);

    if ((isset($result['total_count']))
      && (isset($result['time_entries']))
      && (isset($result['limit']))
      // Fail if case redmine forces less than $pager.
      && ($result['limit'] == $pager)) {

      $page_count = floor($result['total_count'] / $pager) + 1;

      if ($page_count == 1) {
        $entries = $result['time_entries'];
      }
      else {
        $entries = $result['time_entries'];
        // We need to start flipping pages.
        for ($page = 2; $page <= $page_count; $page++) {
          $entries = array_merge($entries, $this->redmine_get_time_entries($start, $end, $pager, ($page - 1) * $pager)['time_entries']);
        }
      }

      return $entries;
    }
    else {
      return [];
    }
  }

  protected function redmine_get_time_entries($start, $end, $pager = 1, $offset = 0) {
    $url = "https://" . $this->RedmineApiKey . ":DefinitelyNotMyPassword@" . $this->RedmineDomain . "/time_entries.json?limit=" . $pager . "&offset=" . $offset . "&user_id=" . $this->RedmineUserId . "&spent_on=><" . $start . "|" . $end;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    $buffer = curl_exec($curl);
    curl_close($curl);

    $json_result = json_decode($buffer, TRUE);

    if ((isset($json_result['total_count'])) && (isset($json_result['time_entries']))) {
      return $json_result;
    }
    else {
      return [];
    }
  }

}