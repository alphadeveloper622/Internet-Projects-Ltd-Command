<?php
namespace Console;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Mailer; 
//use Symfony\Component\Mailer\Transport\SendmailTransport; 
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use \PDO;


class FetchFruitsCommand extends Command
{
    protected static $defaultName = 'fruits:fetch';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Create HTTP client
        $client = new Client();

        // Make a GET request to the API endpoint
        $response = $client->get('https://fruityvice.com/api/fruit/all');

        // Parse the JSON response
        $fruits = json_decode($response->getBody(), true);

        // Connect to the database
        $pdo = new PDO('mysql:host=localhost;dbname=fruit', 'root', '');

        // Create table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS fruits (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255),
            image VARCHAR(255),
            calories INT,
            carbohydrates INT,
            protein INT,
            fat INT
        )");

        // Insert fruits into database
        $stmt = $pdo->prepare("INSERT INTO fruits (name, calories, carbohydrates, protein, fat) VALUES (?, ?, ?, ?, ?)");

        foreach ($fruits as $fruit) {
            $stmt->execute([
                $fruit['name'],
                $fruit['nutritions']['calories'],
                $fruit['nutritions']['carbohydrates'],
                $fruit['nutritions']['protein'],
                $fruit['nutritions']['fat']
            ]);
        }

        //$transport = new SendmailTransport(); 
        //$transport = Transport::fromDsn('smtp://username:password@hostname:port');
        $transport = Transport::fromDsn('sendmail://default'); 
        $mailer = new Mailer($transport);
        $email = (new Email())
            ->from('sender@example.com')
            ->to('alphadeveloper622@gmail.com')
            ->subject('Fruit Fetch')
            ->text('Fetching completed successfully.');

        $mailer->send($email);
        // Output success message
        $output->writeln('Fruits fetched and saved to database.');

        return Command::SUCCESS;
    }
}
