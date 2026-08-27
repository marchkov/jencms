const { Editor, Image: ImageExtension, StarterKit } = window.JenCmsTiptap || {};

const editorInstances = [];
let hasUnsavedEditorChanges = false;

window.addEventListener('beforeunload', (event) => {
  if (!hasUnsavedEditorChanges) {
    return;
  }

  event.preventDefault();
  event.returnValue = '';
});

function toolbarButton(label, title, command) {
  const button = document.createElement('button');
  button.type = 'button';
  const icons = {
    bold: '<path d="M7 5h5a3 3 0 0 1 0 6H7z"/><path d="M7 11h6a3 3 0 0 1 0 6H7z"/>',
    italic: '<path d="M10 5h6M8 19h6M14 5 10 19"/>',
    underline: '<path d="M7 5v6a5 5 0 0 0 10 0V5M5 21h14"/>',
    strike: '<path d="M5 12h14M16 7.5C15.3 6.5 14 6 12.3 6 10.2 6 9 7 9 8.4c0 1.2.8 1.9 2.4 2.4M8 16c.9 1.3 2.3 2 4.3 2 2.1 0 3.7-.9 3.7-2.5 0-1.2-.7-1.9-2.1-2.4"/>',
    bulletList: '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>',
    orderedList: '<path d="M10 6h10M10 12h10M10 18h10M4 5h1v3M3.5 11.5c.3-.5.8-.8 1.4-.8.9 0 1.5.5 1.5 1.2 0 1-1.1 1.5-2.8 3h3M3.5 17h1.4c.9 0 1.5.4 1.5 1s-.6 1-1.5 1H3.5M5.7 19c.6.1 1 .5 1 1 0 .7-.7 1.2-1.8 1.2-.7 0-1.3-.2-1.7-.6"/>',
    blockquote: '<path d="M7 10h4v4H7c0 2-1 3-3 4M15 10h4v4h-4c0 2-1 3-3 4"/>',
    horizontalRule: '<path d="M4 12h16"/>',
    link: '<path d="M10 13a5 5 0 0 0 7.1.1l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1M14 11a5 5 0 0 0-7.1-.1l-2 2A5 5 0 0 0 12 20l1.1-1.1"/>',
    unlink: '<path d="m9 15-2.1 2.1A5 5 0 0 1-.2 10l2-2A5 5 0 0 1 7 6.8M15 9l2.1-2.1A5 5 0 0 1 24.2 14l-2 2a5 5 0 0 1-5.2 1.2M8 2v3M2 8h3M16 19v3M19 16h3" transform="scale(.82) translate(2.5 2.5)"/>',
    undo: '<path d="M9 8 5 12l4 4M5 12h8a5 5 0 0 1 5 5"/>',
    redo: '<path d="m15 8 4 4-4 4M19 12h-8a5 5 0 0 0-5 5"/>',
    clear: '<path d="m5 19 4-4M6 5l13 13M8.5 4.5l11 11-4 4h-5l-6-6a3 3 0 0 1 0-4z"/>',
    source: '<path d="m8 9-4 3 4 3M16 9l4 3-4 3M14 5l-4 14"/>',
    fullscreen: '<path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"/>',
    image: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 20"/>',
  };

  if (icons[command]) {
    button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true">' + icons[command] + '</svg>';
  } else {
    button.textContent = label;
  }
  button.title = title;
  button.setAttribute('aria-label', title);
  button.dataset.command = command;
  return button;
}

function toolbarGroup(items) {
  const group = document.createElement('div');
  group.className = 'editor-toolbar__group';
  items.forEach((item) => group.appendChild(toolbarButton(item[0], item[1], item[2])));
  return group;
}

function initializeTiptap(textarea) {
  const shell = document.createElement('div');
  shell.className = 'editor-shell';

  const toolbar = document.createElement('div');
  toolbar.className = 'editor-toolbar';
  toolbar.setAttribute('role', 'toolbar');
  toolbar.setAttribute('aria-label', 'Content formatting');

  [
    [['P', 'Paragraph', 'paragraph'], ['H2', 'Heading 2', 'h2'], ['H3', 'Heading 3', 'h3'], ['H4', 'Heading 4', 'h4']],
    [['B', 'Bold', 'bold'], ['I', 'Italic', 'italic'], ['U', 'Underline', 'underline'], ['S', 'Strike through', 'strike']],
    [['â€¢', 'Bullet list', 'bulletList'], ['1.', 'Numbered list', 'orderedList'], ['â', 'Block quote', 'blockquote'], ['â€”', 'Horizontal rule', 'horizontalRule']],
    [['Link', 'Add or edit link', 'link'], ['Unlink', 'Remove link', 'unlink'], ['IMG', 'Insert image', 'image']],
    [['Undo', 'Undo', 'undo'], ['Redo', 'Redo', 'redo'], ['Clear', 'Clear formatting', 'clear']],
    [['HTML', 'Edit HTML source', 'source'], ['Full', 'Toggle fullscreen', 'fullscreen']],
  ].forEach((items) => toolbar.appendChild(toolbarGroup(items)));

  const surface = document.createElement('div');
  surface.className = 'editor-surface';
  shell.appendChild(toolbar);
  shell.appendChild(surface);
  textarea.parentNode.insertBefore(shell, textarea);
  shell.appendChild(textarea);
  textarea.classList.add('rich-editor-source');

  let sourceMode = textarea.dataset.editorMode === 'source';
  let visualContentChanged = false;
  const form = textarea.closest('form');
  const mediaDataElement = form?.querySelector('.js-editor-media-data');
  let mediaImages = [];
  let mediaUploadUrl = '';
  try {
    const mediaData = mediaDataElement ? JSON.parse(mediaDataElement.textContent || '{}') : {};
    mediaImages = Array.isArray(mediaData.images) ? mediaData.images : [];
    mediaUploadUrl = typeof mediaData.uploadUrl === 'string' ? mediaData.uploadUrl : '';
  } catch (error) {
    mediaImages = [];
  }

  const editor = new Editor({
    element: surface,
    extensions: [
      StarterKit.configure({
        heading: { levels: [2, 3, 4] },
        link: {
          openOnClick: false,
          autolink: true,
          linkOnPaste: true,
          HTMLAttributes: { rel: 'noopener noreferrer' },
        },
      }),
      ImageExtension.configure({
        allowBase64: false,
        HTMLAttributes: { loading: 'lazy' },
      }),
    ],
    content: textarea.value || '<p></p>',
    editorProps: {
      attributes: {
        'aria-label': textarea.getAttribute('aria-label') || 'Content editor',
      },
    },
    onUpdate: ({ editor: currentEditor }) => {
      textarea.value = currentEditor.getHTML();
      visualContentChanged = true;
      hasUnsavedEditorChanges = true;
      updateToolbar();
    },
    onSelectionUpdate: updateToolbar,
  });
  shell.classList.toggle('is-source', sourceMode);

  function setSourceMode(enabled) {
    if (enabled === sourceMode) {
      return;
    }

    sourceMode = enabled;
    shell.classList.toggle('is-source', sourceMode);

    if (sourceMode) {
      textarea.value = editor.getHTML();
      textarea.focus();
    } else {
      editor.commands.setContent(textarea.value || '<p></p>');
      editor.commands.focus();
    }

    updateToolbar();
  }

  function editLink() {
    const currentHref = editor.getAttributes('link').href || 'https://';
    const href = window.prompt('Enter URL', currentHref);

    if (href === null) {
      return;
    }

    if (href.trim() === '') {
      editor.chain().focus().extendMarkRange('link').unsetLink().run();
      return;
    }

    editor.chain().focus().extendMarkRange('link').setLink({ href: href.trim() }).run();
  }

  function openImagePicker() {
    const dialog = document.createElement('dialog');
    dialog.className = 'editor-image-dialog';
    dialog.innerHTML = '<div class="editor-image-dialog__header"><h3>Insert image</h3><button type="button" class="editor-image-dialog__close" aria-label="Close">Ã—</button></div>' +
      '<div class="editor-image-dialog__body">' +
        '<div class="editor-image-dialog__upload"><input type="file" class="js-image-file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"><button type="button" class="js-image-upload">Upload</button></div>' +
        '<div class="editor-image-dialog__status js-image-upload-status" aria-live="polite"></div>' +
        '<div class="field"><label>Image URL</label><input type="text" class="js-image-url" placeholder="https://example.com/image.jpg or /uploads/image.jpg"></div>' +
        '<div class="field"><label>Alternative text</label><input type="text" class="js-image-alt" placeholder="Describe the image"></div>' +
        '<strong>Media Library</strong><div class="editor-image-dialog__grid"></div>' +
      '</div>' +
      '<div class="editor-image-dialog__actions"><button type="button" class="button button--secondary js-image-cancel">Cancel</button><button type="button" class="js-image-insert">Insert image</button></div>';

    const urlInput = dialog.querySelector('.js-image-url');
    const altInput = dialog.querySelector('.js-image-alt');
    const grid = dialog.querySelector('.editor-image-dialog__grid');
    const fileInput = dialog.querySelector('.js-image-file');
    const uploadButton = dialog.querySelector('.js-image-upload');
    const uploadStatus = dialog.querySelector('.js-image-upload-status');

    function selectMediaItem(item, selectedButton = null) {
      urlInput.value = item.path;
      if (!altInput.value) {
        altInput.value = item.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ');
      }
      grid.querySelectorAll('.editor-image-dialog__item').forEach((candidate) => candidate.classList.remove('is-selected'));
      selectedButton?.classList.add('is-selected');
    }

    function createMediaButton(item) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'editor-image-dialog__item';
      button.title = item.name;

      const preview = document.createElement('img');
      preview.src = item.url;
      preview.alt = '';
      preview.loading = 'lazy';

      const name = document.createElement('span');
      name.textContent = item.name;
      button.appendChild(preview);
      button.appendChild(name);
      button.addEventListener('click', () => selectMediaItem(item, button));
      return button;
    }

    if (mediaImages.length === 0) {
      const empty = document.createElement('p');
      empty.className = 'muted';
      empty.textContent = 'No images in the Media Library yet.';
      grid.appendChild(empty);
    } else {
      mediaImages.forEach((item) => {
        grid.appendChild(createMediaButton(item));
      });
    }

    uploadButton.disabled = !mediaUploadUrl;
    uploadButton.addEventListener('click', async () => {
      const file = fileInput.files?.[0];
      if (!file) {
        uploadStatus.textContent = 'Choose an image first.';
        uploadStatus.className = 'editor-image-dialog__status js-image-upload-status is-error';
        return;
      }

      const csrfToken = form?.querySelector('input[name="_csrf"]')?.value || '';
      const body = new FormData();
      body.append('_csrf', csrfToken);
      body.append('media_file', file);
      uploadButton.disabled = true;
      uploadStatus.textContent = 'Uploadingâ€¦';
      uploadStatus.className = 'editor-image-dialog__status js-image-upload-status';

      try {
        const response = await fetch(mediaUploadUrl, {
          method: 'POST',
          headers: { Accept: 'application/json' },
          body,
        });
        const result = await response.json();
        if (!response.ok || !result.ok || !result.file) {
          throw new Error(result.error || 'Unable to upload the image.');
        }

        mediaImages.unshift(result.file);
        const empty = grid.querySelector('.muted');
        empty?.remove();
        const button = createMediaButton(result.file);
        grid.prepend(button);
        selectMediaItem(result.file, button);
        uploadStatus.textContent = 'Uploaded to Media Library.';
        uploadStatus.className = 'editor-image-dialog__status js-image-upload-status is-success';
        fileInput.value = '';
      } catch (error) {
        uploadStatus.textContent = error instanceof Error ? error.message : 'Unable to upload the image.';
        uploadStatus.className = 'editor-image-dialog__status js-image-upload-status is-error';
      } finally {
        uploadButton.disabled = !mediaUploadUrl;
      }
    });

    function closeDialog() {
      dialog.close();
      dialog.remove();
      editor.commands.focus();
    }

    dialog.querySelector('.editor-image-dialog__close').addEventListener('click', closeDialog);
    dialog.querySelector('.js-image-cancel').addEventListener('click', closeDialog);
    dialog.addEventListener('cancel', (event) => {
      event.preventDefault();
      closeDialog();
    });
    dialog.querySelector('.js-image-insert').addEventListener('click', () => {
      const src = urlInput.value.trim();
      if (!src) {
        urlInput.focus();
        return;
      }
      editor.chain().focus().setImage({ src, alt: altInput.value.trim() }).run();
      closeDialog();
    });

    document.body.appendChild(dialog);
    dialog.showModal();
    urlInput.focus();
  }

  function runCommand(command) {
    const chain = editor.chain().focus();
    const commands = {
      paragraph: () => chain.setParagraph().run(),
      h2: () => chain.toggleHeading({ level: 2 }).run(),
      h3: () => chain.toggleHeading({ level: 3 }).run(),
      h4: () => chain.toggleHeading({ level: 4 }).run(),
      bold: () => chain.toggleBold().run(),
      italic: () => chain.toggleItalic().run(),
      underline: () => chain.toggleUnderline().run(),
      strike: () => chain.toggleStrike().run(),
      bulletList: () => chain.toggleBulletList().run(),
      orderedList: () => chain.toggleOrderedList().run(),
      blockquote: () => chain.toggleBlockquote().run(),
      horizontalRule: () => chain.setHorizontalRule().run(),
      link: editLink,
      image: openImagePicker,
      unlink: () => chain.extendMarkRange('link').unsetLink().run(),
      undo: () => chain.undo().run(),
      redo: () => chain.redo().run(),
      clear: () => chain.unsetAllMarks().clearNodes().run(),
      source: () => setSourceMode(!sourceMode),
      fullscreen: () => {
        shell.classList.toggle('is-fullscreen');
        document.body.classList.toggle('has-fullscreen-editor', shell.classList.contains('is-fullscreen'));
      },
    };

    if (commands[command]) {
      commands[command]();
    }

    updateToolbar();
  }

  function updateToolbar() {
    const active = {
      paragraph: editor.isActive('paragraph'),
      h2: editor.isActive('heading', { level: 2 }),
      h3: editor.isActive('heading', { level: 3 }),
      h4: editor.isActive('heading', { level: 4 }),
      bold: editor.isActive('bold'),
      italic: editor.isActive('italic'),
      underline: editor.isActive('underline'),
      strike: editor.isActive('strike'),
      bulletList: editor.isActive('bulletList'),
      orderedList: editor.isActive('orderedList'),
      blockquote: editor.isActive('blockquote'),
      link: editor.isActive('link'),
      source: sourceMode,
      fullscreen: shell.classList.contains('is-fullscreen'),
    };

    toolbar.querySelectorAll('button[data-command]').forEach((button) => {
      const command = button.dataset.command;
      button.classList.toggle('is-active', Boolean(active[command]));
      button.setAttribute('aria-pressed', active[command] ? 'true' : 'false');
      button.disabled = sourceMode && command !== 'source' && command !== 'fullscreen';
    });
  }

  toolbar.addEventListener('mousedown', (event) => event.preventDefault());
  toolbar.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-command]');
    if (button) {
      runCommand(button.dataset.command);
    }
  });

  textarea.addEventListener('input', () => {
    if (sourceMode) {
      textarea.dataset.sourceDirty = '1';
      hasUnsavedEditorChanges = true;
    }
  });

  textarea.form?.addEventListener('submit', () => {
    if (!sourceMode && visualContentChanged) {
      textarea.value = editor.getHTML();
    }
    hasUnsavedEditorChanges = false;
  });

  updateToolbar();
  editorInstances.push(editor);
}

if (Editor && ImageExtension && StarterKit) {
  document.querySelectorAll('textarea.js-rich-editor').forEach((textarea) => {
    try {
      initializeTiptap(textarea);
    } catch (error) {
      const shell = textarea.closest('.editor-shell');
      if (shell?.parentNode) {
        shell.parentNode.insertBefore(textarea, shell);
        shell.remove();
      }
      textarea.classList.remove('rich-editor-source');
      console.error('Unable to initialize the JenCMS editor.', error);
    }
  });
}
