document.addEventListener('livewire:init', () => {
    Livewire.on('insert-text', ({ text }) => {
        // Находим редактор и вставляем текст
        setTimeout(() => {
            const editor = document.querySelector('.tox-edit-area iframe');
            if (editor && editor.contentWindow) {
                const editorBody = editor.contentWindow.document.body;
                editorBody.innerHTML = '<p>' + text.replace(/\n/g, '</p><p>') + '</p>';
            }
        }, 100);
    });
});