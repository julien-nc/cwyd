<?php

/**
 * Nextcloud - ContextChat
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Anupam Kumar <kyteinsky@gmail.com>
 * @copyright Anupam Kumar 2024
 */

declare(strict_types=1);

namespace OCA\ContextChat\Migration;

use Closure;
use OCA\ContextChat\AppInfo\Application;
use OCA\ContextChat\BackgroundJobs\IndexerJob;
use OCA\ContextChat\BackgroundJobs\InitialContentImportJob;
use OCA\ContextChat\BackgroundJobs\SchedulerJob;
use OCA\ContextChat\BackgroundJobs\StorageCrawlJob;
use OCA\ContextChat\BackgroundJobs\SubmitContentJob;
use OCP\BackgroundJob\IJobList;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version4000Date20241108004215 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $db,
		private IJobList $jobList,
		private IConfig $config,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return void
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		$output->startProgress(10);
		foreach ([
			SchedulerJob::class,
			StorageCrawlJob::class,
			IndexerJob::class,
			InitialContentImportJob::class,
			SubmitContentJob::class,
		] as $className) {
			$this->jobList->remove($className);
			$output->advance(1);
		}


		try {
			$qb = $this->db->getQueryBuilder();
			$qb->delete('context_chat_delete_queue')->executeStatement();
		} catch (Exception $e) {
			$output->warning($e->getMessage());
		}
		$output->advance(1);

		try {
			$qb = $this->db->getQueryBuilder();
			$qb->delete('context_chat_content_queue')->executeStatement();
		} catch (Exception $e) {
			$output->warning($e->getMessage());
		}
		$output->advance(1);

		try {
			$qb = $this->db->getQueryBuilder();
			$qb->delete('context_chat_queue')->executeStatement();
		} catch (Exception $e) {
			$output->warning($e->getMessage());
		}
		$output->advance(1);

		$this->config->setAppValue(Application::APP_ID, 'providers', '');
		$output->advance(1);

		$this->jobList->add(SchedulerJob::class);

		$output->finishProgress();
	}
}
