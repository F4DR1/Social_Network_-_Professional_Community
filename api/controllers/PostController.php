<?php
    require_once 'core/Helpers.php';

    class PostController {
        private $db;
        private $auth;
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }



        // Получаем контент постов
        private function getContentOfPosts($posts) {
            $postIds = array_column($posts, 'id');
            if (empty($postIds)) return [];

            $placeholders = implode(',', array_fill(0, count($postIds), '?'));
            $postContents = [];


            // Статьи
            $articlesData = $this->db->fetchAll("
                    SELECT
                        pa.post_id,
                        a.id,
                        a.title,
                        f.file_path AS cover_media,
                        f.file_title AS cover_media_title,
                        a.content_html,
                        a.read_time,
                        a.created_at,
                        a.updated_at
                    FROM
                        post_articles pa
                        JOIN articles a ON pa.article_id = a.id
                        LEFT JOIN files f ON a.cover_media_id = f.id
                    WHERE
                        pa.post_id IN ($placeholders)
                ",
                $postIds
            );
            foreach ($articlesData as $row) {
                $postContents[$row['post_id']]['article'] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'coverMediaUrl' => Helpers::apiBaseUrl() . '/' . $row['cover_media'],
                    'coverMediaTitle' => $row['cover_media_title'],
                    'readTime' => $row['read_time']
                ];
            }

            // Файлы
            $filesData = $this->db->fetchAll("
                    SELECT
                        pf.post_id,
                        f.id AS media_id,
                        f.file_path AS media_url,
                        f.file_title AS media_title
                    FROM
                        post_files pf
                        JOIN files f ON pf.file_id = f.id
                    WHERE
                        pf.post_id IN ($placeholders)
                ",
                $postIds
            );
            foreach ($filesData as $row) {
                $postContents[$row['post_id']]['files'][] = [
                    'id' => $row['media_id'],
                    'title' => $row['media_title'],
                    'url' => Helpers::apiBaseUrl() . '/' . $row['media_url']
                ];
            }


            
            // Прикрепляем контент к постам
            foreach ($posts as &$post) {
                $post['content'] = [
                    'article' => $postContents[$post['id']]['article'] ?? null,
                    'files' => $postContents[$post['id']]['files'] ?? []
                ];
            }
            unset($post);


            return $posts;
        }
        
        // Возвращает посты пользователя/группы
        private function getAllPosts($type, $id) {
            $posts = $this->db->fetchAll("
                    -- Посты пользователя
                    SELECT
                        p.*,

                        NULL AS group_id,
                        NULL AS group_linkname,
                        NULL AS group_name,
                        NULL AS group_photo,

                        u.linkname AS author_linkname,
                        CONCAT(u.firstname, ' ', u.lastname) AS author_name,
                        fu.file_path AS author_photo
                    FROM
                        posts p
                        INNER JOIN users u ON p.author_id = u.id
                        LEFT JOIN files fu ON fu.id = u.photo_id
                    WHERE
                        ? = 'user'
                        AND p.author_id = ?
                        AND NOT EXISTS (
                            SELECT 1 FROM group_posts gp WHERE gp.post_id = p.id
                        )
                    
                    UNION ALL
                    
                    -- Посты группы
                    SELECT
                        p.*,

                        g.id AS group_id,
                        g.linkname AS group_linkname,
                        g.name AS group_name,
                        fg.file_path AS group_photo,

                        u.linkname AS author_linkname,
                        CONCAT(u.firstname, ' ', u.lastname) AS author_name,
                        fu.file_path AS author_photo
                    FROM
                        posts p
                        INNER JOIN group_posts gp ON p.id = gp.post_id
                        INNER JOIN `groups` g ON gp.group_id = g.id
                        INNER JOIN users u ON p.author_id = u.id
                        LEFT JOIN files fu ON fu.id = u.photo_id
                        LEFT JOIN files fg ON fg.id = g.photo_id
                    WHERE
                        ? = 'group'
                        AND gp.group_id = ?
                    
                    ORDER BY
                        created_at DESC
                ",
                [$type, $id, $type, $id]
            );
            

            $posts = $this->getContentOfPosts($posts);

            
            Helpers::jsonResponse(['success' => true, 'posts' => $posts ?: []]);
        }
        



        
        /**
         * GET /posts/feed - получить все посты в ленте
         */
        public function getAllByFeed() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];


            Helpers::jsonResponse(['success' => true, 'posts' => null]);
        }
        
        /**
         * GET /posts/user/{user_id} - получить все посты пользователя
         */
        public function getAllByUser($userId) {
            Helpers::validateUserId($userId);
            $this->getAllPosts('user', $userId);
        }
        
        /**
         * GET /posts/group/{group_id} - получить все посты группы
         */
        public function getAllByGroup($groupId) {
            Helpers::validateGroupId($groupId);
            $this->getAllPosts('group', $groupId);
        }

        /**
         * GET /post/get/{post_id} - получить пост
         */
        public function get($postId) {
            Helpers::validatePostId($postId);

            $post = $this->db->fetchOne("
                    SELECT
                        *
                    FROM
                        posts
                    WHERE
                        id = ?
                ",
                [$postId]
            );
            
            if (!$post) {
                Helpers::errorResponse('Пост не найден', 404);
            }
            
            Helpers::jsonResponse(['success' => true, 'post' => $post]);
        }
        
        /**
         * POST /post/publicate - опубликовать пост
         */
        public function publicate() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $data = json_decode(file_get_contents('php://input'), true);

            $content = $data['content'] ?? null;
            $dataText = $data['text'] ?? '';
            $groupId = $data['groupId'] ?? null;
            
            $postText = trim($dataText) ?: null;
            
            
            if (!empty($groupId))
                Helpers::validateGroupId($groupId);
            
            // Проверка содержимого поста
            if (empty($postText) && empty($content)) {
                Helpers::errorResponse('Содержимое поста обязательно', 400);
            }
            if (!empty($content) && empty($content['type'])) {
                Helpers::errorResponse('Неверный формат прикреплённого контента', 400);
            }



            // Создание поста
            try {
                $this->db->beginTransaction();

                // Создание поста
                $this->db->query("
                        INSERT INTO posts (author_id, text, created_at, updated_at) 
                        VALUES (?, ?, NOW(), NOW())
                    ",
                    [$currentUserId, $postText]
                );
                $postId = $this->db->lastInsertId();


                // Соединение другого контента с постом
                if (!empty($content)) {
                    switch ($content['type']) {
                        case 'article':
                            // Статьи
                            $this->db->query("
                                    INSERT INTO post_articles (post_id, article_id) 
                                    VALUES (?, ?)
                                ",
                                [$postId, $content['id']]
                            );
                            break;
                        
                        case 'files':
                            // Файлы
                            $filesIds = $content['filesIds'];
                            if (!empty($filesIds)) {
                                $rowPlaceholders = [];
                                $params = [];
                                foreach ($filesIds as $fileId) {
                                    $rowPlaceholders[] = '(?, ?)';
                                    $params[] = $postId;
                                    $params[] = $fileId;
                                }
                                $values = implode(', ', $rowPlaceholders);

                                $this->db->query("
                                        INSERT INTO post_files (post_id, file_id)
                                        VALUES $values
                                    ",
                                    $params
                                );
                            }
                            break;

                        default:
                            throw new Exception('Неверный тип контента.');
                    }
                }
                

                // Публикация в группе (если это группа)
                if (!empty($groupId)) {
                    $this->db->query("
                            INSERT INTO group_posts (group_id, post_id) 
                            VALUES (?, ?)
                        ",
                        [$groupId, $postId]
                    );
                }
                

                $this->db->commit();
                Helpers::jsonResponse(['success' => true]);

            } catch (Exception $e) {
                $this->db->rollBack();
                Helpers::errorResponse('Ошибка создания поста: ' . $e->getMessage(), 500);
            }
        }
        
        /**
         * POST /post/delete - удалить пост
         */
        public function delete() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $data = json_decode(file_get_contents('php://input'), true);
            $postId = $data['postId'] ?? null;
            $groupId = $data['groupId'] ?? null;
            
            
            Helpers::validatePostId($postId);
            if (!empty($groupId))
                Helpers::validateGroupId($groupId);


            try {
                $this->db->beginTransaction();

                if (!empty($groupId))
                    $this->db->query("
                            DELETE
                            FROM
                                group_posts
                            WHERE
                                group_id = ?
                                AND
                                post_id = ?
                        ",
                        [$groupId, $postId]
                    );

                $this->db->query("
                        DELETE
                        FROM
                            posts
                        WHERE
                            id = ?
                            AND
                            author_id = ?
                    ",
                    [$postId, $currentUserId]
                );

                $this->db->commit();
                Helpers::jsonResponse(['success' => true]);

            } catch (Exception $e) {
                $this->db->rollBack();
                Helpers::errorResponse('Ошибка удаления поста: ' . $e->getMessage(), 500);
            }
        }
    }
?>
