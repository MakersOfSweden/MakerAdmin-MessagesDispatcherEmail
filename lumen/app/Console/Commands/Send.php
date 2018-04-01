<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Libraries\CurlBrowser;

use DB;

use Mailgun\Mailgun;

class Send extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = "service:send";

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Send all queued messages to the mail service provider";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->info("Dispatching queued messages");

		// Instantiate the Mailgunclient
		$mgClient = new Mailgun(config("mailgun.key"))
		$mailgun_domain = config("mailgun.domain");
		$mailgun_from = config("mailgun.from");
		$mailgun_limit = 10;

		// Get all messages from database
		$messages = DB::table("messages_recipient")
			->leftJoin("messages_message", "messages_message.messages_message_id", "=", "messages_recipient.messages_message_id")
			->selectRaw("messages_message.messages_message_id AS message_id")
			->selectRaw("messages_recipient.messages_recipient_id AS recipient_id")
			->selectRaw("messages_recipient.title AS subject")
			->selectRaw("messages_recipient.description AS body")
			->selectRaw("messages_recipient.recipient AS recipient")
			->where("messages_recipient.status", "=", "queued")
			->where("messages_message.message_type", "=", "email")
			->limit($mailgun_limit)
			->get();

		// Create an clean array with data
		$list = [];
		foreach($messages as $message)
		{
			echo "Sending mail to {$message->recipient}\n";

			try {
				// Send the mail via Mailgun
				$result = $mgClient->sendMessage($mailgun_domain,
					array(
						'from'    => $mailgun_from,
						'to'      => 'Christian Antila <christian.antila@gmail.com>', // TODO
						'subject' => $message->subject,
						'text'    => $message->body
					)
				);

				// Update the database and flag the E-mail as sent
				$meep = DB::table("messages_recipient")
					->where("messages_recipient_id", $message->recipient_id)
					->update([
						"status"    => "sent",
						"date_sent" => date("Y-m-d H:i:s"),
					]
				);

				// TODO: Uppdatera status i messages
			}
			catch(Exception $e)
			{
				// TODO: Error handling
				echo "Error\n";
			}
		}
	}
}
