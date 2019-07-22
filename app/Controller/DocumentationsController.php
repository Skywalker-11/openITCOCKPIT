<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.
use App\Model\Table\DocumentationsTable;
use App\Model\Table\HostsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\ServicetemplatesTable;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;

/**
 * Class DocumentationsController
 * @property Documentation $Documentation
 * @property AppAuthComponent $Auth
 */
class DocumentationsController extends AppController {

    public $layout = 'blank';


    /**
     * @param null $uuid
     * @param string $type
     * @throws Exception
     */
    public function view($uuid = null, $type = 'host') {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }

        if (empty($type)) {
            throw new InvalidArgumentException();
        }
        $type = strtolower($type);

        /** @var $DocumentationsTable DocumentationsTable */
        $DocumentationsTable = TableRegistry::getTableLocator()->get('Documentations');
        $allowEdit = false;

        switch ($type) {
            case 'host':
                /** @var $HostsTable HostsTable */
                $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
                $host = $HostsTable->getHostByUuid($uuid);

                //Can user see this object?
                if (!$this->allowedByContainerId($host->getContainerIds(), false)) {
                    $this->render403();
                    return;
                }

                //Can user edit this object?
                $allowEdit = $this->allowedByContainerId($host->getContainerIds());

                break;

            case 'service':
                /** @var $ServicesTable ServicesTable */
                $ServicesTable = TableRegistry::getTableLocator()->get('Services');
                $service = $ServicesTable->getServiceByUuid($uuid);

                //Can user see this object?
                if (!$this->allowedByContainerId($service->get('host')->getContainerIds(), false)) {
                    $this->render403();
                    return;
                }

                //Can user edit this object?
                $allowEdit = $this->allowedByContainerId($service->get('host')->getContainerIds());
                break;

            case 'hosttemplate':
                /** @var $HosttemplatesTable HosttemplatesTable */
                $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
                $hosttemplate = $HosttemplatesTable->getHosttemplateByUuid($uuid);

                //Can user see this object?
                if (!$this->allowedByContainerId($hosttemplate['Hosttemplate']['container_id'], false)) {
                    $this->render403();
                    return;
                }

                //Can user edit this object?
                $allowEdit = $this->allowedByContainerId($hosttemplate['Hosttemplate']['container_id']);
                break;

            case 'servicetemplate':
                /** @var $ServicetemplatesTable ServicetemplatesTable */
                $ServicetemplatesTable = TableRegistry::getTableLocator()->get('Servicetemplates');
                $servicetemplate = $ServicetemplatesTable->getServicetemplateById($uuid);

                //Can user see this object?
                if (!$this->allowedByContainerId($servicetemplate['Servicetemplate']['container_id'], false)) {
                    $this->render403();
                    return;
                }

                //Can user edit this object?
                $allowEdit = $this->allowedByContainerId($servicetemplate['Servicetemplate']['container_id']);
                break;

            default:
                throw new InvalidArgumentException('Type not supported.');
                break;
        }


        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->Auth);
            $UserTime = $User->getUserTime();

            $docuExists = $DocumentationsTable->existsByUuid($uuid);
            $lastUpdate = $UserTime->format(time());
            if ($docuExists) {
                $documentation = $DocumentationsTable->getDocumentationByUuid($uuid);

                /** @var FrozenTime $modified */
                $modified = $documentation->get('modified');
                $lastUpdate = $UserTime->format($modified->getTimestamp());
            }

            $this->set('lastUpdate', $lastUpdate);
            $this->set('allowEdit', $allowEdit);
            $this->set('docuExists', $docuExists);
            $this->set('bbcode', $documentation->get('content'));
            $this->set('_serialize', ['lastUpdate', 'allowEdit', 'docuExists', 'bbcode']);

            return;
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            if ($allowEdit === false) {
                $this->render403();
                return;
            }
            $content = $this->request->data('content');

            if ($DocumentationsTable->existsByUuid($uuid)) {
                $entity = $DocumentationsTable->getDocumentationByUuid($uuid);
            } else {
                $entity = $DocumentationsTable->newEntity();
            }

            $entity = $DocumentationsTable->patchEntity($entity, [
                'content' => $content
            ]);

            $DocumentationsTable->save($entity);
            if ($entity->hasErrors()) {
                $this->response->statusCode(400);
                $this->set('error', $entity->getErrors());
                $this->set('_serialize', ['error']);
                return;
            }

            $this->set('documentation', $entity);
            $this->set('_serialize', ['documentation']);
            return;

        }
    }

    /**
     * @throws Exception
     */
    public function wiki() {
        if (!$this->isAngularJsRequest()) {
            //Only ship template
            return;
        }

        $documentations = [
            'additional_help' => [
                'name'      => ('Additional Help'),
                'directory' => 'additional_help',
                'children'  => [
                    'mysql_performance'          => [
                        'name'        => __('MySQL performance'),
                        'description' => 'A few tips to optimize your MySQL performance.',
                        'file'        => 'mysql_performance',
                        'icon'        => 'fa fa-database',
                    ],
                    'browser_push_notifications' => [
                        'name'        => __('Browser Push Notifications'),
                        'description' => 'How to setup openITCOCKPIT browser push notifications',
                        'file'        => 'browser_push_notifications',
                        'icon'        => 'fa fa-bell-o',
                    ],
                    'markdown'                   => [
                        'name'        => __('Markdown'),
                        'description' => 'A cheatsheet to help writing markdown formatted texts. ',
                        'file'        => 'markdown',
                        'icon'        => 'fa fa-pencil',
                    ],
                ],
            ],
        ];

        if ($this->request->is('get')) {
            $this->set('documentations', $documentations);
            $this->set('_serialize', ['documentations']);
            return;
        }

        if ($this->request->is('post')) {

            $category = $this->request->data('category');
            $documentation = $this->request->data('documentation');

            if (!isset($documentations[$category]['children'][$documentation])) {
                throw new NotFoundException();
            }

            $file = OLD_APP . 'docs' . DS . 'en' . DS . $documentations[$category]['directory'] . DS . $documentations[$category]['children'][$documentation]['file'] . '.md';
            if (!file_exists($file)) {
                throw new NotFoundException('Markdown file not found!');
            }

            $parsedown = new ParsedownExtra();
            $html = $parsedown->text(file_get_contents($file));

            $this->set('documentation', $documentations[$category]['children'][$documentation]);
            $this->set('html', $html);
            $this->set('_serialize', ['documentation', 'html']);
            return;
        }
    }
}
