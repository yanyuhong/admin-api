<?php

/**
 * Created by PhpStorm.
 * User: yyh
 * Date: 2018/6/30
 * Time: 下午6:55
 */
use App\Library\Core\BaseController;
use App\Library\Help\Arr;
use App\Library\Error;
use App\Models\Filter;
use App\Models\FormField;

class PageController extends BaseController
{
    public function homeListAction()
    {
        $pages = ServBox()->PageService()->getHomeList();
        $data = [];
        foreach ($pages as $page) {
            if (!isset($data[$page['group']])) {
                $data[$page['group']] = [
                    'title' => $page['group'],
                    'pages' => [],
                ];
            }
            $data[$page['group']]['pages'][] = [
                'id' => $page['id'],
                'title' => $page['title']
            ];
        }

        $this->sendSuccess([
            'groups' => array_values($data)
        ]);
    }

    public function getConfigAction()
    {
        $pageId = Arr::get($this->get, 'page_id');
        $page = ServBox()->PageService()->getPage($pageId);

        if (!$page) {
            $this->sendResult(Error::$PAGE_ERROR);
        }

        $data = [
            'title' => $page['title'],
            'type' => (string)$page['type'],
            'isSub' => $page['parent_id'] ? '1' : '2',
            'filters' => [],
            'forms' => [],
            'operators' => [],
            'headers' => [],
        ];

        $filters = ServBox()->PageService()->getFilters($pageId);
        foreach ($filters as $filter) {
            $options = [];
            if ($filter['type'] == Filter::TYPE_SELECT) {
                $options = ServBox()->CommonService()->getOption($filter['data']);
            }
            $data['filters'][] = [
                'label' => $filter['label'],
                'name' => $filter['name'],
                'type' => (string)$filter['type'],
                'default' => $filter['default'],
                'tip' => $filter['tip'],
                'labelWidth' => (string)$filter['label_width'],
                'valueWidth' => (string)$filter['value_width'],
                'options' => $options,
            ];
        }

        $forms = ServBox()->PageService()->getForms($pageId);
        foreach ($forms as $form) {
            $fields = [];
            $formFields = ServBox()->PageService()->getFormFields($form['id']);
            foreach ($formFields as $formField) {
                $options = [];
                if ($formField['type'] == FormField::TYPE_SELECT || $formField['type'] == FormField::TYPE_SELECT_MORE) {
                    $options = ServBox()->CommonService()->getOption($formField['data']);
                }
                $fields[] = [
                    'label' => $formField['label'],
                    'name' => $formField['name'],
                    'type' => (string)$formField['type'],
                    'default' => $formField['default'],
                    'required' => (string)$formField['required'],
                    'disabled' => (string)$formField['disabled'],
                    'tip' => $formField['tip'],
                    'options' => $options,
                ];
            }
            $data['forms'][] = [
                'id' => (string)$form['id'],
                'name' => (string)$form['name'],
                'fields' => $fields,
            ];
        }

        $operates = ServBox()->PageService()->getOperates($pageId);
        foreach ($operates as $operate) {
            $data['operators'][] = [
                'id' => (string)$operate['id'],
                'label' => $operate['label'],
                'select' => (string)$operate['select'],
                'formId' => (string)$operate['form_id'],
                'confirmText' => (string)$operate['confirm_text'],
            ];
        }

        $headers = ServBox()->PageService()->getHeaders($pageId);
        foreach ($headers as $header) {
            $data['headers'][] = [
                'label' => $header['label'],
                'name' => $header['name'],
                'type' => (string)$header['type'],
                'visible' => (string)$header['visible'],
            ];
        }

        $this->sendSuccess($data);
    }
}