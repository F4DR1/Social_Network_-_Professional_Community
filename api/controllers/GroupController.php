<?php
    require_once 'core/Helpers.php';

    class GroupController {
        private $db;
        private $auth;
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }


        
        /**
         * Проверяет является ли пользователь участником группы
         */
        public static function checkIsMember($db, $groupId, $currentUserId) {
            // Проверка членства в чате
            $isMember = $db->fetchOne("
                    SELECT 1
                    FROM
                        group_members
                    WHERE
                        group_id = ?
                        AND
                        user_id = ?
                ",
                [$groupId, $currentUserId]
            );
            if (!$isMember) Helpers::errorResponse('Вы не являетесь участником этой группы', 403);
        }

        /**
         * Проверяет является ли пользователь администратором группы
         */
        public static function checkIsAdmin($db, $groupId, $currentUserId) {
            $adminRoles = Helpers::getGroupAdminRoles();
            $adminRolesNames = $adminRoles['names'];
            $isAdmin = $db->fetchOne("
                    SELECT 1
                    FROM
                        group_members gm
                        INNER JOIN
                            group_roles gr ON gm.role_id = gr.id
                    WHERE
                        gm.group_id = ?
                        AND
                        gm.user_id = ?
                        AND
                        gr.name IN ($adminRolesNames)
                    LIMIT 1
                ",
                array_merge([$groupId, $currentUserId], $adminRoles['roles'])
            );
            if (!$isAdmin) return false;
            return true;
        }



        /**
         * Выполняет запрос группы по переданному условию и параметрам,
         * затем обрабатывает и отпраляет ответ.
         */
        private function fetchGroup(string $where, array $params): void {
            $sql = "
                SELECT
                    g.id,
                    g.linkname,
                    g.name,
                    f.file_path AS photo
                FROM
                    `groups` g
                    LEFT JOIN files f ON f.id = g.photo_id
                WHERE
                    $where
            ";
            $group = $this->db->fetchOne($sql, $params);
            if (!$group) Helpers::errorResponse('Группа не найдена', 404);
            
            $group['photo'] = Helpers::fileUrl($group['photo'] ?? Helpers::imagePlaceholder('group'));
            
            Helpers::jsonResponse(['success' => true, 'group' => $group]);
        }

        



        /**
         * GET /groups/{group_id} - получить данные группы по id
         */
        public function getGroupById($groupId) {
            Helpers::validateGroupId($groupId);
            $this->fetchGroup('g.id = ?', [$groupId]);
        }
        
        /**
         * GET /groups/{linkname} - получить данные группы по linkname
         */
        public function getGroupByLinkname($linkname) {
            $this->fetchGroup("g.linkname = ?", [$linkname]);
        }
        
        
        /**
         * GET /groups/members/{group_id} - получить список участников группы
         */
        public function members($groupId) {
            Helpers::validateGroupId($groupId);

            $members = $this->db->fetchAll("
                    SELECT
                        u.id,
                        u.linkname,
                        u.firstname,
                        f.file_path AS photo
                    FROM
                        group_members gm 
                        INNER JOIN users u ON gm.user_id = u.id
                        LEFT JOIN files f ON f.id = u.photo_id
                    WHERE
                        gm.group_id = ?
                ",
                [$groupId]
            );

            // Преобразуем относительные пути в полные URL
            foreach ($members as &$member) {
                $member['photo'] = Helpers::fileUrl($member['photo'] ?? Helpers::imagePlaceholder('user'));
            }
            unset($member);
            
            Helpers::jsonResponse(['success' => true, 'members' => $members]);
        }
        

        /**
         * GET /groups/list/{user_id} - получаем списки групп пользователя
         */
        public function getUserGroups($userId) {
            Helpers::validateUserId($userId);
            
            try {
                $adminRoles = Helpers::getGroupAdminRoles();
                $adminRoleNames = $adminRoles['roles'];

                // Один запрос для получения всех групп пользователя с ролями
                $sql = "
                    SELECT DISTINCT
                        g.id,
                        g.linkname,
                        g.name,
                        f.file_path AS photo,
                        gm.role_id,
                        gr.name as role_name,
                        gr.title as role_title
                    FROM
                        `groups` g
                        INNER JOIN group_members gm ON g.id = gm.group_id
                        INNER JOIN group_roles gr ON gm.role_id = gr.id
                        LEFT JOIN files f ON f.id = g.photo_id
                    WHERE
                        gm.user_id = ?
                    ORDER BY
                        gm.joined_at DESC
                ";
                
                $allGroups = $this->db->fetchAll($sql, [$userId]);
                
                $groups = ['all' => [], 'admin' => []];
                $seenAll = [];
                $seenAdmin = [];
                
                foreach ($allGroups as $group) {
                    $groupId = $group['id'];
                    
                    // Добавляем в общий список, если ещё не добавлена
                    if (!in_array($groupId, $seenAll)) {
                        $groups['all'][] = $group;
                        $seenAll[] = $groupId;
                    }
                    
                    // Проверяем, является ли роль административной (сравниваем по имени)
                    if (in_array($group['role_name'], $adminRoleNames)) {
                        if (!in_array($groupId, $seenAdmin)) {
                            $groups['admin'][] = $group;
                            $seenAdmin[] = $groupId;
                        }
                    }
                }
                
                Helpers::jsonResponse(['success' => true, 'groups' => $groups ?: null]);
                
            } catch (Exception $e) {
                Helpers::errorResponse('Не удалось получить список групп', 404);
            }
        }

        /**
         * GET /groups/is-admin/{group_id}/{user_id} - проверяем является ли пользователь администратором группы
         */
        public function getUserIsAdminGroup($groupId, $userId) {
            Helpers::validateGroupId($groupId);
            Helpers::validateUserId($userId);

            $isAdmin = self::checkIsAdmin($this->db, $groupId, $userId);

            Helpers::jsonResponse(['success' => true, 'isAdmin' => $isAdmin]);
        }
        
        /**
         * GET /groups/status/subscribe/{group_id} - проверить статус подписки на группу
         */
        public function statusSubscribe($groupId) {
            $this->auth->check();
            
            Helpers::validateGroupId($groupId);

            $currentUserId = $this->auth->getCurrentUser()['id'];
            $status = $this->db->fetchOne("
                    SELECT
                        gm.user_id IS NOT NULL as isSubscribe,
                        gr.name = ? as isOwner
                    FROM
                        group_members gm
                        LEFT JOIN
                            group_roles gr ON gm.role_id = gr.id
                    WHERE
                        gm.user_id = ?
                        AND
                        gm.group_id = ?
                    LIMIT 1
                ",
                ['owner', $currentUserId, $groupId]
            );
            
            Helpers::jsonResponse([
                'success' => true,
                'isSubscribe' => $status['isSubscribe'] ?? false,
                'isOwner' => $status['isOwner'] ?? false
            ]);
        }
        
        /**
         * POST /groups/subscribe - подписаться на группу
         */
        public function subscribe() {
            $this->auth->check();
            $currentUser = $this->auth->getCurrentUser();
            
            $data = json_decode(file_get_contents('php://input'), true);
            $groupId = $data['groupId'];

            Helpers::validateGroupId($groupId);
            
            
            try {
                $this->db->query("
                        INSERT INTO group_members (user_id, group_id, joined_at) 
                        VALUES (?, ?, NOW())
                    ",
                    [$currentUser['id'], $groupId]
                );

                Helpers::jsonResponse(['success' => true]);

            } catch (Exception $e) {
                Helpers::errorResponse('Ошибка подписки', 409);
            }
        }
        
        /**
         * POST /groups/unsubscribe - отписаться от группы
         */
        public function unsubscribe() {
            $this->auth->check();
            $currentUser = $this->auth->getCurrentUser();
            
            $data = json_decode(file_get_contents('php://input'), true);
            $groupId = $data['groupId'];

            Helpers::validateGroupId($groupId);
            
            try {
                $this->db->query("
                        DELETE
                        FROM
                            group_members
                        WHERE
                            user_id = ?
                            AND
                            group_id = ?
                    ",
                    [$currentUser['id'], $groupId]
                );

                Helpers::jsonResponse(['success' => true]);

            } catch (Exception $e) {
                Helpers::errorResponse('Ошибка отписки', 409);
            }
        }
        

        /**
         * POST /groups/create - создать группу
         */
        public function createGroup() {
            $this->auth->check();
            $currentUser = $this->auth->getCurrentUser();
            
            $data = json_decode(file_get_contents('php://input'), true);
            $groupName = $data['name'];

            // Валидация входных данных
            if (empty($groupName) || strlen($groupName) < 4) {
                Helpers::errorResponse('Название группы должно содержать минимум 4 символа', 400);
                return;
            }

            try {
                $this->db->beginTransaction();

                $this->db->query("
                        INSERT INTO `groups` (name, created_at) 
                        VALUES (?, NOW())
                    ",
                    [$groupName]
                );
                
                $groupId = $this->db->lastInsertId();

                $role = $this->db->fetchOne("
                        SELECT
                            id
                        FROM
                            group_roles
                        WHERE
                            name = ?
                    ",
                    ['owner']
                );

                if (!$role) {
                    throw new Exception('Роль "owner" не найдена');
                }

                $this->db->query("
                        INSERT INTO group_members (group_id, user_id, role_id, joined_at) 
                        VALUES (?, ?, ?, NOW())
                    ",
                    [$groupId, $currentUser['id'], $role['id']]
                );

                $this->db->commit();
                Helpers::jsonResponse(['success' => true, 'groupId' => $groupId]);

            } catch (Exception $e) {
                $this->db->rollBack();
                Helpers::errorResponse('Ошибка создания группы: ' . $e->getMessage(), 500);
            }
        }
        
        /**
         * POST /groups/edit - редактировать группу
         */
        public function editGroup() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $data = json_decode(file_get_contents('php://input'), true);
            $groupId = $data['groupId'] ?? null;
            $category = $data['category'] ?? null;
            
            Helpers::validateGroupId($groupId);


            // Проверка админ ли группы
            $isAdmin = self::checkIsAdmin($this->db, $groupId, $currentUserId);
            if (!$isAdmin) Helpers::errorResponse('Нет прав на редактирование', 403);
            
            
            
            try {
                switch ($category) {
                    case 'base':
                        // Изменяем базовую информацию
                        $baseJson = $data['base'] ?? null;
                        $base = json_decode($baseJson, true);

                        if (!$base) Helpers::errorResponse('Неверные данные', 400);


                        $groupName = $base['name'];
                        $groupLinkname = trim($base['linkname']);

                        // Валидация входных данных
                        Helpers::validateNameLength($groupName);
                        Helpers::validateLinknameLength($groupLinkname);


                        // Проверяем ссылку
                        $noneLinkname = "group$groupId";
                        if ($groupLinkname === $noneLinkname) {
                            $groupLinkname = null;
                        } else {
                            // Проверка ссылки на верный формат
                            if (!Helpers::isValidLinknameFormat($groupLinkname))
                                Helpers::errorResponse('Ссылка не должна быть формата "user123" или "group123"!', 400);
                            
                            // Проверка ссылки на занятость
                            if (!Helpers::isLinknameUnique($this->db, $groupLinkname, excludeGroupId: $groupId))
                                Helpers::errorResponse('Ссылка уже занята', 400);
                        }


                        // Обновляем данные
                        $this->db->query("
                                UPDATE
                                    `groups`
                                SET
                                    name = ?,
                                    linkname = ? 
                                WHERE
                                    id = ?
                            ",
                            [$groupName, $groupLinkname, $groupId]
                        );
                        
                        Helpers::jsonResponse(['success' => true, 'linkname' => $groupLinkname ?: $noneLinkname]);
                        break;
                    
                    default:
                        Helpers::errorResponse('Не удалось получить данные для редактирования', 400);
                        break;
                }

            } catch (Exception $e) {
                Helpers::errorResponse('Ошибка редактирования группы', 500);
            }
        }
    }
?>
