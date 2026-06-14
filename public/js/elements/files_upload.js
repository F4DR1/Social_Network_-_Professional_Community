// files_uploads.js
import {
    fileUpload
} from '../api.js';

document.addEventListener('DOMContentLoaded', function() {

    
    const MAX_FILES = 10;  // Лимит за раз
    const MAX_SIZE = 10 * 1024 * 1024;  // Размер файла 10 МБ



    // Создать HTML панели загрузки изображения
    window.createUploadImageHTML = function(parentId, imageFilePreviewPanelId) {
        const inputId = 'imageFileInput';
        const uploadBtnId = 'imageFileUploadButton';
        
        // Панель загрузки файлов
        const panelHTML = `
            <div class="image-upload-panel">
                <input type="file" id="${inputId}" accept="image/*" style="display: none;" />
                <button class="modal-btn image-upload" id="${uploadBtnId}">
                    Прикрепить обложку с устройства
                </button>
            </div>
        `
        document.getElementById(parentId).insertAdjacentHTML("beforeend", panelHTML);


        // Элементы панели
        const fileInput = document.getElementById(inputId);
        const previewDiv = document.getElementById(imageFilePreviewPanelId);


        // Кнопка открывает диалог выбора файла
        document.getElementById(uploadBtnId).addEventListener('click', () => {
            fileInput.click();
        });


        // Обработка выбранного файла
        fileInput.addEventListener('change', async () => {
            const files = Array.from(fileInput.files);
            if (files.length === 0) return;


            // Инициализируем переменную для ID (глобально)
            window.selectedFileIds = [];

            
            // Берём только первый файл (если их вдруг несколько)
            const fileToUpload = files[0];
            
            // Очищаем preview
            previewDiv.innerHTML = '';


            // Проверка на клиенте для файла
            const allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp'
            ];

            if (!allowedTypes.includes(fileToUpload.type)) {
                await confirmationModal(
                    `Файл "${file.name}" не поддерживается. Разрешены: JPEG, PNG, GIF, WebP.`,
                    'Неразрешённый тип файла',
                    'Хорошо'
                );
                return;
            }
            if (fileToUpload.size > MAX_SIZE) {
                await confirmationModal(
                    `Файл "${fileToUpload.name}" слишком большой (максимум 10 МБ).`,
                    'Файл слишком большой',
                    'Хорошо'
                );
                return;
            }

            // Готовим FormData для файла
            const formData = new FormData();
            formData.append('file', fileToUpload);

            // Загружаем файл
            const uploadedFile = await uploadFileAPI(formData);

            
            // Добавляем ID в массив
            window.selectedFileIds.push(uploadedFile.id);

            // Показываем превью
            await renderPreviewHTML(uploadedFile, previewDiv);


            // Сбрасываем input, чтобы можно было повторно выбрать те же файлы
            fileInput.value = '';
        });
    }
    
    // Создать HTML панели загрузки файлов
    window.createUploadFilesPanelHTML = function(parentId, filesPreviewPanelId, handlerFunction = null) {
        const inputId = 'filesInput';
        const uploadBtnId = 'filesUploadButton';

        // Панель загрузки файлов
        const panelHTML = `
            <div class="files-upload-panel">
                <input type="file" id="${inputId}" accept="image/*" multiple style="display: none;" />
                <button class="modal-btn files-upload" id="${uploadBtnId}">
                    Прикрепить файлы с устройства
                </button>
            </div>
        `
        document.getElementById(parentId).insertAdjacentHTML("afterbegin", panelHTML);


        // Элементы панели
        const fileInput = document.getElementById(inputId);
        const previewDiv = document.getElementById(filesPreviewPanelId);


        // Кнопка открывает диалог выбора файла
        document.getElementById(uploadBtnId).addEventListener('click', () => {
            fileInput.click();
        });


        // Обработка выбранных файлов
        fileInput.addEventListener('change', async () => {
            const files = Array.from(fileInput.files);
            if (files.length === 0) return;


            // Инициализируем массив для новых ID (глобально)
            window.selectedFileIds = window.selectedFileIds || [];


            // Ограничиваем количество файлов
            if (files.length > MAX_FILES || (files.length + window.selectedFileIds.length) > MAX_FILES) {
                await confirmationModal(
                    `Можно загрузить не более ${MAX_FILES} файлов.`,
                    'Слишком много файлов',
                    'Хорошо'
                );
                fileInput.value = '';
                return;
            }


            for (const file of files) {
                // Проверки на клиенте для каждого файла
                const allowedTypes = [
                    'image/jpeg', 'image/png', 'image/gif', 'image/webp'
                ];

                if (!allowedTypes.includes(file.type)) {
                    await confirmationModal(
                        `Файл "${file.name}" не поддерживается. Разрешены: JPEG, PNG, GIF, WebP.`,
                        'Неразрешённый тип файла',
                        'Хорошо'
                    );
                    continue;
                }
                if (file.size > MAX_SIZE) {
                    await confirmationModal(
                        `Файл "${file.name}" слишком большой (максимум 10 МБ).`,
                        'Файл слишком большой',
                        'Хорошо'
                    );
                    continue;
                }

                // Готовим FormData для текущего файла
                const formData = new FormData();
                formData.append('file', file);

                // Загружаем файл
                const uploadedFile = await uploadFileAPI(formData);

                
                // Добавляем ID в массив
                window.selectedFileIds.push(uploadedFile.id);

                // Показываем превью
                await renderPreviewHTML(uploadedFile, previewDiv, handlerFunction);
            }

            // Сбрасываем input, чтобы можно было повторно выбрать те же файлы
            fileInput.value = '';


            handlerFunction();  // Вызов кастомного обработчика при прикреплении файла
        });
    }



    // Загрузить изображение на сервер
    async function uploadFileAPI(formData) {
        console.log('Загрузка файла на сервер...');
        try {
            const result = await fileUpload(formData);
            
            if (result.success) {
                return result.file;

            } else {
                await confirmationModal(
                    'Произошла ошибка при загрузке файла: ' + result.error,
                    'Ошибка загрузки',
                    'Хорошо'
                );
            }

        } catch (err) {
            console.error('Ошибка:', err);
            await confirmationModal(
                'Произошла сетевая ошибка. Попробуйте загрузить файл позднее.',
                'Сетевая ошибка',
                'Хорошо'
            );   
        }
    }



    // Форматируем размер в читаемый для пользователя формат
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' Б';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' КБ';
        return (bytes / (1024 * 1024)).toFixed(1) + ' МБ';
    }
    
    // Создаём HTML предпросмотра
    function renderPreviewHTML(file, previewDiv, handlerFunction) {
        const previewId = 'FilePreviewId' + file.id;
        const previewDeleteBtnId = 'RemoveBtn' + previewId;

        const removeFileBtn = `
            <button class="preview-remove-btn" id="${previewDeleteBtnId}" data-file-id="${file.id}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        `;


        let previewHTML = '';

        if (file.mimeType.startsWith('image/')) {
            // Изображение – показываем саму картинку
            const image = file.url || '';
            previewHTML = `
                <div class="image-preview" id="${previewId}" style="position:relative; display:inline-block;">
                    <img src="${image}" alt="${file.title}" style="max-width:200px; max-height:200px;">
                    ${removeFileBtn}
                </div>
            `;

        } else if (file.mimeType.startsWith('video/')) {
            // Видео – нужны poster и duration
            const poster = file.posterUrl || '';  // Постер
            const duration = file.duration || 0;  // Длительность в секундах
            const minutes = Math.floor(duration / 60);
            const seconds = Math.floor(duration % 60).toString().padStart(2, '0');
            previewHTML = `
                <div class="video-preview" id="${previewId}" style="position:relative; display:inline-block;">
                    <img src="${poster}" style="max-width:200px; max-height:200px;">
                    <span style="position:absolute; bottom:4px; right:4px; background:rgba(0,0,0,0.7); color:white; padding:2px 6px; border-radius:4px; font-size:0.8em;">
                        ${minutes}:${seconds}
                    </span>
                    ${removeFileBtn}
                </div>`;
            
        } else {
            // Остальные файлы – иконка с расширением
            const ext = file.extension.toUpperCase();
            previewHTML = `
                <div class="file-preview" id="${previewId}" style="display:flex; align-items:center; gap:8px; padding:8px; border:1px solid #ddd; border-radius:6px;">
                    <div class="extension" style="width:48px; height:48px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; font-weight:bold; color:#555;">
                        <span>${ext}</span>
                    </div>
                    <div>
                        <div style="font-weight:500;">${file.title}</div>
                        <div style="font-size:0.8em; color:#666;">${formatFileSize(file.size)}</div>
                    </div>
                    ${removeFileBtn}
                </div>`;
        }

        
        // Добавляем элемент в preview панель
        previewDiv.insertAdjacentHTML('beforeend', previewHTML);


        // Находим только что добавленный элемент
        const previewElement = document.getElementById(previewId);
        const deleteBtn = document.getElementById(previewDeleteBtnId);

        // Удаление файла из preview
        deleteBtn.addEventListener('click', async () => {
            previewElement.remove();
            window.selectedFileIds = window.selectedFileIds.filter(id => id !== file.id);

            handlerFunction();  // Вызов кастомного обработчика при прикреплении файла
        });
    }
});
