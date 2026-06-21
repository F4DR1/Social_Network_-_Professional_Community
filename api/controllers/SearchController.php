<?php
    require_once 'core/Helpers.php';

    class SearchController {
        private $db;
        private $auth;
        
        // Конструктор получает подключение к БД и объект авторизации
        public function __construct($db, $auth) {
            $this->db = $db;
            $this->auth = $auth;
        }
        


        /**
         * POST /searches/search - поиск
         */
        public function search() {
            $this->auth->check();
            $currentUserId = $this->auth->getCurrentUser()['id'];

            $data = json_decode(file_get_contents('php://input'), true);
            $category = $data['category'] ?? null;
            $text = $data['text'] ?? '';


            if (empty($category)) Helpers::errorResponse('Не указана категория для поиска', 400);
            

            // Формируем запрос в зависимости от категории
            $sql = '';
            $params = [];
            switch ($category) {
                case 'users':
                    $sql = "
                        SELECT
                            u.id,
                            u.linkname,
                            CONCAT(u.firstname, ' ', u.lastname) AS fullname,
                            f.file_path AS photo
                        FROM
                            users u
                            LEFT JOIN files f ON f.id = u.photo_id
                    ";
                    if (!empty($text)) {
                        $sql .= " WHERE (CONCAT(u.firstname, ' ', u.lastname) LIKE ? OR u.linkname LIKE ?)";
                        $likeText = '%' . $text . '%';
                        $params = [$likeText, $likeText];
                    }
                    break;

                case 'groups':
                    $sql = "
                        SELECT 
                            g.id,
                            g.linkname,
                            g.name,
                            f.file_path AS photo
                        FROM
                            `groups` g
                            LEFT JOIN files f ON g.photo_id = f.id
                    ";
                    if (!empty($text)) {
                        $sql .= " WHERE (g.name LIKE ? OR g.linkname LIKE ?)";
                        $likeText = '%' . $text . '%';
                        $params = [$likeText, $likeText];
                    }
                    break;
                
                default:
                    Helpers::errorResponse('Недопустимая категория поиска', 400);
                    break;
            }

            // Выполняем запрос
            $results = $this->db->fetchAll($sql, $params);

            // Преобразуем путь к фото в полный URL или подставляем заглушку
            foreach ($results as &$item) {
                $placeholderType = ($category === 'users') ? 'user' : 'group';
                $item['photo'] = Helpers::fileUrl($item['photo'] ?? Helpers::imagePlaceholder($placeholderType));
            }

            Helpers::jsonResponse(['success' => true, 'list' => $results]);
        }
    }
?>
