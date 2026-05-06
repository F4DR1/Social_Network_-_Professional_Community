<?php
    require_once 'core/Helpers.php';

    class UserSkillController {
        private $db;
        private $auth;
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }
        


        /**
         * GET /skills/levels/get - получить список уровней
         */
        public function getSkillLevels() {
            $levels = $this->db->fetchAll("
                    SELECT
                        *
                    FROM
                        skill_levels
                ",
                []
            );
            
            Helpers::jsonResponse(['success' => true, 'levels' => $levels]);
        }
        
        /**
         * POST /skills/get - получить список навыков с указанием, есть ли навык у пользователя
         */
        public function getSkills() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $data = json_decode(file_get_contents('php://input'), true);
            $userSkillsIds = $data['userSkillsIds'] ?? null;

            // Формируем условие для исключения уже добавленных навыков
            if (!empty($userSkillsIds)) {
                $placeholders = rtrim(str_repeat('?,', count($userSkillsIds)), ',');
                $inClause = "AND s.id NOT IN ($placeholders)";
                $params = $userSkillsIds;
            } else {
                $inClause = '';
                $params = [];
            }

            $skills = $this->db->fetchAll("
                    SELECT
                        s.id AS id,
                        s.name AS name,
                        --us.id AS user_skill_id,
                        --us.level_id AS user_skill_level_id,
                        CASE WHEN us.id IS NOT NULL THEN 1 ELSE 0 END AS has_skill
                    FROM
                        skills s
                        LEFT JOIN
                            user_skills us ON us.skill_id = s.id AND us.user_id = ?
                    ORDER BY
                        s.name
                ",
                [$currentUserId]
            );
            
            Helpers::jsonResponse(['success' => true, 'skills' => $skills]);
        }
        
        /**
         * POST /users/user-skills/get/{user_id} - получить скиллы пользователя
         */
        public function getUserSkills($userId) {
            Helpers::validateUserId($userId);
            
            $data = json_decode(file_get_contents('php://input'), true);
            $currentUserId = $data['currentUserId'] ?? null;

            if (!empty($currentUserId))
                Helpers::validateUserId($currentUserId);

    
            // Если currentUserId не задан, используем 0 (несуществующий ID) – тогда is_endorsement всегда 0
            $endorserId = !empty($currentUserId) ? $currentUserId : 0;
            
            $userSkills = $this->db->fetchAll("
                    SELECT
                        us.id AS user_skill_id,
                        s.name AS name,
                        lvl.id AS level_id,
                        lvl.name AS level_name,
                        lvl.title AS level_title,
                        COUNT(se.id) AS endorsements_count,
                        (
                            SELECT COUNT(*) > 0
                            FROM skill_endorsements se2
                            WHERE se2.user_skill_id = us.id
                            AND se2.user_id = ?
                        ) AS is_endorsement
                    FROM
                        user_skills us
                        INNER JOIN
                            skills s ON us.skill_id = s.id
                        INNER JOIN
                            skill_levels lvl ON us.level_id = lvl.id
                        LEFT JOIN
                            skill_endorsements se ON se.user_skill_id = us.id
                    WHERE
                        us.user_id = ?
                    GROUP BY 
                        us.id,
                        us.skill_id,
                        us.level_id,
                        s.name,
                        lvl.name,
                        lvl.title
                    ORDER BY
                        endorsements_count DESC,
                        s.name;
                ",
                [$endorserId, $userId]
            );
            
            Helpers::jsonResponse(['success' => true, 'skills' => $userSkills]);
        }
        
        /**
         * POST /users/user-skills/add - добавить скилл пользователя
         */
        public function addUserSkill() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];
            
            // Получаем данные из тела запроса (JSON)
            $data = json_decode(file_get_contents('php://input'), true);
            
            $skillId = $data['skillId'] ?? null;
            $skillLevelId = $data['skillLevelId'] ?? null;

            Helpers::validateSkillId($skillId);
            if (!empty($skillLevelId))
                Helpers::validateSkillLevelId($skillLevelId);
            else {
                $beginnerSkill = $this->db->fetchOne("
                        SELECT
                            id
                        FROM
                            skill_levels
                        WHERE
                            name = ?
                        LIMIT 1
                    ",
                    ['beginner']
                );
                $skillLevelId = $beginnerSkill['id'];
            }


            // Проверка что skillId и skillLevelId есть в базе
            $skillIsExist = $this->db->fetchOne("
                    SELECT 1
                    FROM
                        skills
                    WHERE
                        id = ?
                    LIMIT 1
                ",
                [$skillId]
            );
            if (empty($skillIsExist)) {
                Helpers::errorResponse('Нет такого навыка в списке навыков', 404);
                return;
            }
            $skillLevelIsExist = $this->db->fetchOne("
                    SELECT 1
                    FROM
                        skill_levels
                    WHERE
                        id = ?
                    LIMIT 1
                ",
                [$skillLevelId]
            );
            if (empty($skillLevelIsExist)) {
                Helpers::errorResponse('Нет такого уровня навыка в списке уровней навыков', 404);
                return;
            }

            
            // Не даём создавать несколько записей одного навыка
            $userSkillIsExist = $this->db->fetchOne("
                    SELECT 1
                    FROM
                        user_skills
                    WHERE
                        user_id = ?
                        AND
                        skill_id = ?
                    LIMIT 1
                ",
                [$currentUserId, $skillId]
            );
            if (!empty($userSkillIsExist)) {
                Helpers::errorResponse('Навык уже был добавлен', 400);
                return;
            }

            
            $this->db->query("
                    INSERT INTO user_skills (user_id, skill_id, level_id, created_at)
                    VALUES (?, ?, ?, NOW())
                ",
                [$currentUserId, $skillId, $skillLevelId]
            );
            
            Helpers::jsonResponse(['success' => true]);
        }
        
        /**
         * PUT /users/user-skills/edit - редактировать скилл пользователя
         */
        public function editUserSkill() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];
            
            // Получаем данные из тела запроса (JSON)
            $data = json_decode(file_get_contents('php://input'), true);
            
            $userSkillId = $data['userSkillId'] ?? null;
            $newSkillLevelId = $data['skillLevelId'] ?? null;

            Helpers::validateSkillId($userSkillId);
            Helpers::validateSkillLevelId($newSkillLevelId);


            // Проверка что userSkillId и skillLevelId есть в базе
            $userSkillIsExist = $this->db->fetchOne("
                    SELECT 1
                    FROM
                        user_skills
                    WHERE
                        id = ?
                        AND
                        user_id = ?
                    LIMIT 1
                ",
                [$userSkillId, $currentUserId]
            );
            if (empty($userSkillIsExist)) {
                Helpers::errorResponse('Нет такого навыка в списке навыков пользователя', 404);
                return;
            }
            $skillLevelIsExist = $this->db->fetchOne("
                    SELECT 1
                    FROM
                        skill_levels
                    WHERE
                        id = ?
                    LIMIT 1
                ",
                [$newSkillLevelId]
            );
            if (empty($skillLevelIsExist)) {
                Helpers::errorResponse('Нет такого уровня навыка в списке уровней навыков', 404);
                return;
            }

            
            $this->db->query("
                    UPDATE
                        user_skills
                    SET
                        level_id = ?
                    WHERE
                        id = ?
                        AND
                        user_id = ?
                ",
                [$newSkillLevelId, $userSkillId, $currentUserId]
            );
            
            Helpers::jsonResponse(['success' => true]);
        }
        
        /**
         * DELETE /users/user-skills/delete - удалить скилл пользователя
         */
        public function deleteUserSkill() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];
            
            // Получаем данные из тела запроса (JSON)
            $data = json_decode(file_get_contents('php://input'), true);
            
            $userSkillId = $data['userSkillId'] ?? null;

            Helpers::validateSkillId($userSkillId);


            // Проверка что userSkillId есть в базе
            $skillIsExist = $this->db->fetchOne("
                    SELECT 1
                    FROM
                        user_skills
                    WHERE
                        id = ?
                        AND
                        user_id = ?
                    LIMIT 1
                ",
                [$userSkillId, $currentUserId]
            );
            if (empty($skillIsExist)) {
                Helpers::errorResponse('Нет такого навыка в списке навыков пользователя', 404);
                return;
            }
            
            
            $this->db->query("
                    DELETE
                    FROM
                        user_skills
                    WHERE
                        id = ?
                        AND
                        user_id = ?
                ",
                [$userSkillId, $currentUserId]
            );
            
            Helpers::jsonResponse(['success' => true]);
        }
        
        /**
         * POST /users/user-skills/endorsement/add - добавить подтверждение скилла пользователя
         */
        public function addEndorsementUserSkill() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];
            
            // Получаем данные из тела запроса (JSON)
            $data = json_decode(file_get_contents('php://input'), true);
            
            $userId = $data['userId'] ?? null;
            $userSkillId = $data['userSkillId'] ?? null;

            Helpers::validateUserId($userId);
            Helpers::validateSkillId($userSkillId);


            if ($currentUserId === $userId) {
                Helpers::errorResponse('Нельзя подтвердить свой навык', 400);
                return;
            }


            // Проверка что userSkillId есть в базе
            $skillIsExist = $this->db->fetchOne("
                    SELECT 1
                    FROM
                        user_skills
                    WHERE
                        id = ?
                        AND
                        user_id = ?
                    LIMIT 1
                ",
                [$userSkillId, $userId]
            );
            if (empty($skillIsExist)) {
                Helpers::errorResponse('Нет такого навыка в списке навыков пользователя', 404);
                return;
            }
            $endorsementIsExist = $this->db->fetchOne("
                    SELECT 1
                    FROM
                        skill_endorsements
                    WHERE
                        user_skill_id = ?
                        AND
                        user_id = ?
                    LIMIT 1
                ",
                [$userSkillId, $currentUserId]
            );
            if (!empty($endorsementIsExist)) {
                Helpers::errorResponse('Нельзя подтвердить навык второй раз', 400);
                return;
            }

            
            $this->db->query("
                    INSERT INTO skill_endorsements (user_id, user_skill_id, created_at)
                    VALUES (?, ?, NOW())
                ",
                [$currentUserId, $userSkillId]
            );
            
            Helpers::jsonResponse(['success' => true]);
        }
        
        /**
         * DELETE /users/user-skills/endorsement/delete - удалить подтверждение скилла пользователя
         */
        public function deleteEndorsementUserSkill() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];
            
            // Получаем данные из тела запроса (JSON)
            $data = json_decode(file_get_contents('php://input'), true);
            
            $userId = $data['userId'] ?? null;
            $userSkillId = $data['userSkillId'] ?? null;

            Helpers::validateUserId($userId);
            Helpers::validateSkillId($userSkillId);


            // Проверка что userSkillId есть в базе
            $skillIsExist = $this->db->fetchOne("
                    SELECT 1
                    FROM
                        user_skills
                    WHERE
                        id = ?
                        AND
                        user_id = ?
                    LIMIT 1
                ",
                [$userSkillId, $userId]
            );
            if (empty($skillIsExist)) {
                Helpers::errorResponse('Нет такого навыка в списке навыков пользователя', 404);
                return;
            }
            
            
            $this->db->query("
                    DELETE
                    FROM
                        skill_endorsements
                    WHERE
                        user_id = ?
                        AND
                        user_skill_id = ?
                ",
                [$currentUserId, $userSkillId]
            );
            
            Helpers::jsonResponse(['success' => true]);
        }
    }
?>
