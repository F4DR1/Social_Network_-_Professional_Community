document.addEventListener('DOMContentLoaded', function() {

    // Функция создания кастомного выпадающего списка с выбором элемента
    window.selectCustomDropdown = function(parentId, currentElementId, elementsArray) {
        // elementsArray: массив объектов { id, name, title }
        const currentElement = elementsArray.find(el => el.id == currentElementId) || elementsArray[0];
        const dropdownId = `dropdown_${parentId}`;
        
        // HTML список значений
        let optionsHtml = '';
        elementsArray.forEach(el => {
            const isSelected = el.id == currentElementId;
            optionsHtml += `
                <li class="${isSelected ? 'selected' : ''}" data-element-id="${el.id}">
                    ${el.title}
                </li>
            `;
        });
        
        return `
            <div class="custom-dropdown" id="${dropdownId}" data-parent-id="${parentId}" data-current-element-id="${currentElementId}">
                <button class="dropdown-trigger" type="button">
                    <span class="selected-text">${currentElement.title}</span>
                    <svg class="dropdown-arrow" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M7 10l5 5 5-5z"/>
                    </svg>
                </button>
                <ul class="dropdown-menu">
                    ${optionsHtml}
                </ul>
            </div>
        `;
    }

});
