<?php
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Item;
use Bitrix\Main\ORM\Query\Result;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\UserTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\CompanyTable;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();


class castomComponens extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
    public function onPrepareComponentParams($arParams)
    {
        return $arParams;
    }
    public function executeComponent()
    {
        $this->arResult = $this->getDels();
        $this->arResult['HEADER'] = $this->getHeader ($this->arResult['COUNT_CATEGORIES'],$this->arResult["DEAL"]);
        $this->IncludeComponentTemplate('');
    }
    public function getHeader(int $categories, array $deals)
    {
        $head=[];
        for ($i=0; $i<$categories; $i++ ) {
            foreach ($deals[$i][0] as $key => $value) {
                $head[$i][] = $key;
            }
        }
        return $head;
    }
    public function getUsers()
    {
        $result = [];
        $users = UserTable::getList([
            'select' => ['ID', 'NAME', 'LAST_NAME'],
            'filter' => ['ACTIVE' => 'Y'], // только активные пользователи
        ]);
//        echo print_r($result,1);

        while ($user = $users->fetch()) {
            // пользователь с информацией о группах
           $result[$user['ID']] = $user;
        }
        return $result;
    }
    public function getCompany()
    {
        $result = [];
        $companys = CompanyTable::getList([
            'select' => ['ID', 'TITLE'],
        ]);

        while ($company = $companys->fetch()) {
            // пользователь с информацией о группах
           $result[$company['ID']] = $company;
        }
        return $result;
    }
    public function getContact()
    {
        $result = [];
        $contacts = ContactTable::getList([
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'],
        ]);

        while ($contact = $contacts->fetch()) {
            // пользователь с информацией о группах
            $result[$contact['ID']] = $contact;
        }
        return $result;
    }
    public function getDels()
    {
        try {
        if (!Bitrix\Main\Loader::includeModule('crm')) {
            throw new \Exception('Модуль CRM не установлен');
        }

        $dealData       =[];
        $result         =[];
        $dealStatage    =[];
        $categoryName   =[];
        $pattern        = '/"([^"]+)"/';
        $replacement    = '\\"$1\\"';

        $dealFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
        $categories = $dealFactory->getCategories();
        if (!is_iterable($categories)) {
            throw new \Exception('Не удалось получить категории сделок');
        }
        $result['COUNT_CATEGORIES'] = count($categories);

        if ($dealFactory)
        {
            try {
                $usersArray = $this->getUsers();
                $contactArray = $this->getContact();
                $companyArray = $this->getCompany();
            } catch (\Exception $e) {
                throw new \Exception('Ошибка при получении вспомогательных данных: ' . $e->getMessage());
            }
            foreach ($categories as $category) {
                $categoryId     =  $category->getId();
                $categoryName[] =  $category->getName();

                $statages = $dealFactory->getStages($categoryId);
                if (!$statages) {
                    throw new \Exception("Не удалось получить стадии для категории {$categoryId}");
                }
                foreach ($statages->getAll() as $stage) {
                    $stateges = $stage->collectValues();
                    $result['CATEGORIES_STATAGES'][$categoryId][] = $stateges;
                    $dealStatage[$categoryId][$stateges['STATUS_ID']]= [];

                }
                $filter = [
                    '=CATEGORY_ID' => $categoryId,
                ];

                $deals = $dealFactory->getItems([
                        'filter' => $filter,
                        'select' => ['ID', 'CREATED_TIME', 'CREATED_BY', 'UPDATED_BY', 'ASSIGNED_BY_ID', 'COMPANY_ID', 'CONTACT_ID', 'TITLE', 'STAGE_ID', 'OPPORTUNITY'],
                        'order'  => ['ID' => 'DESC'],
                    ]
                );

                foreach ($deals as $deal) {
                    try {
                        $createdBy      = $deal->getCreatedBy();
                        $updatedBy      = $deal->getUpdatedBy();
                        $assignedById   = $deal->getAssignedById();
                        $createdTime    = $deal->getCreatedTime() ? $deal->getCreatedTime()->format('Y-m-d H:i:s') : null;
                        $id             = $deal->getId();
                        $title          = $deal->getTitle();
                        $stage          = $deal->getStageId();
                        $opportunity    = $deal->getOpportunity();
                    $dealData[$categoryId][] = [
                        'ID'                => $id,
                        'CREATED_TIME'      => $createdTime ,
                        'CREATED_BY'        => isset($usersArray[$createdBy]) ?
                            $usersArray[$createdBy]['NAME'] . ' ' . $usersArray[$createdBy]['LAST_NAME'] : 'Неизвестно',
                        'UPDATED_BY'        =>  isset($usersArray[$updatedBy]) ?
                            $usersArray[$updatedBy]['NAME'] . ' ' . $usersArray[$updatedBy]['LAST_NAME'] : 'Неизвестно',
                        'ASSIGNED_BY_ID'    =>  isset($usersArray[$assignedById]) ?
                            $usersArray[$assignedById]['NAME'] . ' ' . $usersArray[$assignedById]['LAST_NAME'] : 'Неизвестно',
                        'COMPANY_ID'        =>  empty($companyArray[$deal->getCompanyId()]['TITLE'])? ' - ' : preg_replace($pattern, $replacement, $companyArray[$deal->getCompanyId()]['TITLE']),
                        'CONTACT_ID'        => empty($contactArray[$deal->getContactId()]['NAME'])? ' - ' : $contactArray[$deal->getContactId()]['NAME'],
                        'TITLE'             => isset($title) ?
                            $deal->getTitle() : 'Неизвестно',
                        'STAGE_ID'          => isset($stage) ?
                            $deal->getStageId() : 'Неизвестно',
                        'OPPORTUNITY'       => isset($opportunity) ?
                            $deal->getOpportunity() : '0',
                    ];
                    $dealStatage[$categoryId][$deal->getStageId()][] = [
                        'ID'                => $id,
                        'CREATED_TIME'      => $createdTime ,
                        'CREATED_BY'        => isset($usersArray[$createdBy]) ?
                            $usersArray[$createdBy]['NAME'] . ' ' . $usersArray[$createdBy]['LAST_NAME'] : 'Неизвестно',
                        'UPDATED_BY'        =>  isset($usersArray[$updatedBy]) ?
                            $usersArray[$updatedBy]['NAME'] . ' ' . $usersArray[$updatedBy]['LAST_NAME'] : 'Неизвестно',
                        'ASSIGNED_BY_ID'    =>  isset($usersArray[$assignedById]) ?
                            $usersArray[$assignedById]['NAME'] . ' ' . $usersArray[$assignedById]['LAST_NAME'] : 'Неизвестно',
                        'COMPANY_ID'        =>  empty($companyArray[$deal->getCompanyId()]['TITLE'])? ' - ' : preg_replace($pattern, $replacement, $companyArray[$deal->getCompanyId()]['TITLE']),
                        'CONTACT_ID'        => empty($contactArray[$deal->getContactId()]['NAME'])? ' - ' : $contactArray[$deal->getContactId()]['NAME'],
                        'TITLE'             => isset($title) ?
                            $deal->getTitle() : 'Неизвестно',
                        'STAGE_ID'          => isset($stage) ?
                            $deal->getStageId() : 'Неизвестно',
                        'OPPORTUNITY'       => isset($opportunity) ?
                            $deal->getOpportunity() : '0',
                    ];
                    } catch (\Exception $e) {
                        // Логируем ошибку, но продолжаем обработку остальных сделок
                        error_log("Ошибка при обработке сделки ID {$deal->getId()}: " . $e->getMessage());
                        continue;
                    }
                }
            }
        }
        else
        {
            throw new \Exception('Не удалось получить фабрику сделок');
        }
        $result['CATEGORIES_NAME']  = $categoryName;
        $result['DEAL']             = $dealData;
        $result['DEAL_STATAGE']     = $dealStatage;
        return $result;
        } catch (\Exception $e) {
            // Логируем критическую ошибку
            error_log("Критическая ошибка в getDels(): " . $e->getMessage());

            // Возвращаем пустую структуру с информацией об ошибке
            return [
                'error' => true,
                'error_message' => $e->getMessage(),
                'COUNT_CATEGORIES' => 0,
                'CATEGORIES_NAME' => [],
                'DEAL' => [],
                'DEAL_STATAGE' => []
            ];
        }
    }
    public  function ajaxAction()
    {
        $data = [];
        $data = $this->getDels();
        $data['HEADER'] = $this->getHeader($data['COUNT_CATEGORIES'],$data["DEAL"]);
        $response = [
            'status' => 'success',
            'data' => $data,
            'message' => 'Данные успешно получены',
            'timestamp' => time()
        ];

        return $response;
    }
    public function configureActions()
    {
        // устанавливаем фильтры (Bitrix\Main\Engine\ActionFilter\Authentication() и Bitrix\Main\Engine\ActionFilter\HttpMethod() и Bitrix\Main\Engine\ActionFilter\Csrf())
        return [
            'ajax' => [
                'prefilters' => [
                    new Bitrix\Main\Engine\ActionFilter\Authentication(),
                    new Bitrix\Main\Engine\ActionFilter\HttpMethod(array(Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_GET, Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST)),
                    new Bitrix\Main\Engine\ActionFilter\Csrf(),
                ],
                'postfilters' => []
            ]
        ];
    }
}