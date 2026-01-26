<template>
    <div class="email-editor-wrapper" :class="{ 'is-fullscreen': isFullscreen }">
        <!-- Loading Overlay -->
        <Transition name="fade">
            <div v-if="isLoading" class="editor-loading-overlay">
                <div class="loading-content">
                    <div class="loading-spinner"></div>
                    <p class="loading-text">Chargement de l'éditeur...</p>
                </div>
            </div>
        </Transition>

        <!-- Toolbar -->
        <div class="editor-toolbar">
            <div class="toolbar-left">
                <div class="toolbar-group">
                    <button @click="undo" class="toolbar-btn" title="Annuler" :disabled="!canUndo">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                    </button>
                    <button @click="redo" class="toolbar-btn" title="Rétablir" :disabled="!canRedo">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6" />
                        </svg>
                    </button>
                </div>
                <div class="toolbar-divider"></div>
                <button @click="toggleFullscreen" class="toolbar-btn">
                    <svg v-if="!isFullscreen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="device-selector">
                <button v-for="d in devices" :key="d.id" @click="setDevice(d.id)" :class="['device-btn', { active: currentDevice === d.id }]">
                    <component :is="d.icon" class="w-5 h-5" />
                    <span class="device-label">{{ d.label }}</span>
                </button>
            </div>

            <div class="toolbar-right">
                <div class="view-toggle">
                    <button @click="currentView = 'visual'" :class="['view-btn', { active: currentView === 'visual' }]">
                        Éditeur
                    </button>
                    <button @click="currentView = 'code'" :class="['view-btn', { active: currentView === 'code' }]">
                        Code
                    </button>
                </div>
                <button @click="openPreview" class="preview-btn">
                    Prévisualiser
                </button>
            </div>
        </div>

        <!-- Main Editor Area -->
        <div class="editor-body">
            <!-- Left Panel - Blocks using GrapesJS Block Manager -->
            <aside class="editor-sidebar left-sidebar" :class="{ collapsed: leftPanelCollapsed }">
                <div class="sidebar-header">
                    <h3 class="sidebar-title" v-show="!leftPanelCollapsed">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Blocs
                    </h3>
                    <button @click="leftPanelCollapsed = !leftPanelCollapsed" class="collapse-btn">
                        <svg class="w-4 h-4" :class="{ 'rotate-180': leftPanelCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                </div>
                <div class="sidebar-content" v-show="!leftPanelCollapsed">
                    <!-- GrapesJS Block Manager Container -->
                    <div ref="blocksPanel" class="blocks-panel"></div>

                    <!-- Variables -->
                    <div class="variables-section">
                        <h4 class="section-subtitle">Variables</h4>
                        <div class="variables-list">
                            <button v-for="v in variables" :key="v.key" @click="insertVariable(v.key)" class="variable-item">
                                <span>{{ v.label }}</span>
                                <code v-text="formatVariable(v.key)"></code>
                            </button>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Canvas -->
            <main class="editor-canvas-wrapper">
                <div class="canvas-container" :class="[`device-${currentDevice}`]">
                    <div v-show="currentView === 'visual'" ref="editorContainer" class="gjs-editor-container"></div>
                    <div v-show="currentView === 'code'" class="code-editor-wrapper">
                        <div class="code-panel">
                            <div class="code-panel-header">
                                <span class="code-badge html">HTML</span>
                            </div>
                            <textarea v-model="htmlCode" @input="debouncedApplyCode" class="code-textarea" spellcheck="false"></textarea>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Right Panel -->
            <aside class="editor-sidebar right-sidebar" :class="{ collapsed: rightPanelCollapsed }">
                <div class="sidebar-header">
                    <button @click="rightPanelCollapsed = !rightPanelCollapsed" class="collapse-btn mr-2">
                        <svg class="w-4 h-4" :class="{ 'rotate-180': !rightPanelCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    <h3 class="sidebar-title" v-show="!rightPanelCollapsed">Propriétés</h3>
                </div>
                <div class="sidebar-content" v-show="!rightPanelCollapsed">
                    <div class="panel-tabs">
                        <button @click="activeTab = 'styles'" :class="['panel-tab', { active: activeTab === 'styles' }]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                            <span>Styles</span>
                        </button>
                        <button @click="activeTab = 'settings'" :class="['panel-tab', { active: activeTab === 'settings' }]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Paramètres</span>
                        </button>
                        <button @click="activeTab = 'layers'" :class="['panel-tab', { active: activeTab === 'layers' }]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span>Calques</span>
                        </button>
                    </div>
                    <div class="panel-content">
                        <div v-show="activeTab === 'styles'" ref="stylesPanel" class="gjs-panel-content"></div>
                        <div v-show="activeTab === 'settings'" ref="traitsPanel" class="gjs-panel-content"></div>
                        <div v-show="activeTab === 'layers'" ref="layersPanel" class="gjs-panel-content layers-panel"></div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Preview Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showPreview" class="preview-overlay" @click.self="showPreview = false">
                    <div class="preview-modal">
                        <div class="preview-header">
                            <span class="preview-title">Prévisualisation</span>
                            <div class="preview-devices">
                                <button v-for="d in ['desktop', 'tablet', 'mobile']" :key="d" @click="previewDevice = d" :class="['preview-device-btn', { active: previewDevice === d }]">
                                    {{ d === 'desktop' ? 'Desktop' : d === 'tablet' ? 'Tablette' : 'Mobile' }}
                                </button>
                            </div>
                            <button @click="showPreview = false" class="preview-close">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="preview-body">
                            <div :class="['preview-frame-container', `preview-${previewDevice}`]">
                                <iframe ref="previewFrame" class="preview-iframe"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, h } from 'vue';
import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';
import debounce from 'lodash/debounce';

const props = defineProps({
    initialContent: { type: String, default: '' },
    initialMjml: { type: String, default: '' }
});

const emit = defineEmits(['update:content']);

// Refs
const editorContainer = ref(null);
const blocksPanel = ref(null);
const stylesPanel = ref(null);
const traitsPanel = ref(null);
const layersPanel = ref(null);
const previewFrame = ref(null);

// State
const editor = ref(null);
const isLoading = ref(true);
const currentDevice = ref('desktop');
const isFullscreen = ref(false);
const currentView = ref('visual');
const showPreview = ref(false);
const previewDevice = ref('desktop');
const leftPanelCollapsed = ref(false);
const rightPanelCollapsed = ref(false);
const activeTab = ref('styles');
const htmlCode = ref('');
const canUndo = ref(false);
const canRedo = ref(false);

// Device icons as render functions
const DesktopIcon = {
    render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' })
    ])
};
const TabletIcon = {
    render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z' })
    ])
};
const MobileIcon = {
    render: () => h('svg', { class: 'w-5 h-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z' })
    ])
};

const devices = [
    { id: 'desktop', label: 'Desktop', icon: DesktopIcon },
    { id: 'tablet', label: 'Tablette', icon: TabletIcon },
    { id: 'mobile', label: 'Mobile', icon: MobileIcon }
];

const variables = [
    { key: 'contact.first_name', label: 'Prénom' },
    { key: 'contact.last_name', label: 'Nom' },
    { key: 'contact.email', label: 'Email' },
    { key: 'contact.company', label: 'Entreprise' },
    { key: 'unsubscribe_url', label: 'Désinscription' },
    { key: 'current_year', label: 'Année' },
];

// Methods
const insertVariable = (key) => {
    if (editor.value) {
        const selected = editor.value.getSelected();
        if (selected && selected.is('text')) {
            const content = selected.get('content') || '';
            selected.set('content', content + `{{${key}}}`);
        } else {
            const wrapper = editor.value.getWrapper();
            wrapper.append(`<span>{{${key}}}</span>`);
        }
    }
};

const formatVariable = (key) => `{{${key}}}`;

const undo = () => editor.value?.UndoManager.undo();
const redo = () => editor.value?.UndoManager.redo();

const setDevice = (deviceId) => {
    currentDevice.value = deviceId;
    const deviceMap = { desktop: 'Desktop', tablet: 'Tablet', mobile: 'Mobile' };
    editor.value?.setDevice(deviceMap[deviceId]);
};

const toggleFullscreen = () => {
    isFullscreen.value = !isFullscreen.value;
};

const openPreview = () => {
    showPreview.value = true;
    nextTick(() => {
        if (previewFrame.value && editor.value) {
            const html = editor.value.getHtml();
            const css = editor.value.getCss();
            previewFrame.value.srcdoc = `<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:0;padding:0;}${css}</style></head><body>${html}</body></html>`;
        }
    });
};

const updateCode = () => {
    if (editor.value) {
        htmlCode.value = editor.value.getHtml();
    }
};

const debouncedApplyCode = debounce(() => {
    if (editor.value && htmlCode.value) {
        editor.value.setComponents(htmlCode.value);
    }
}, 800);

const updateUndoRedo = () => {
    if (editor.value) {
        const um = editor.value.UndoManager;
        canUndo.value = um.hasUndo();
        canRedo.value = um.hasRedo();
    }
};

const getDefaultTemplate = () => `
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:40px 20px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="padding:32px 40px;text-align:center;border-bottom:1px solid #e5e7eb;">
                        <img src="https://via.placeholder.com/150x50/6366f1/ffffff?text=LOGO" alt="Logo" style="max-width:150px;" />
                    </td>
                </tr>
                <tr>
                    <td style="padding:48px 40px;">
                        <h1 style="margin:0 0 16px;font-family:Arial,sans-serif;font-size:28px;font-weight:700;color:#111827;text-align:center;">Bienvenue !</h1>
                        <p style="margin:0 0 24px;font-family:Arial,sans-serif;font-size:16px;line-height:1.7;color:#4b5563;text-align:center;">Merci de nous avoir rejoints. Découvrez nos nouveautés.</p>
                        <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                            <tr>
                                <td style="background:#6366f1;border-radius:8px;">
                                    <a href="#" style="display:inline-block;padding:14px 32px;font-family:Arial,sans-serif;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;">Commencer</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 40px;background:#f9fafb;text-align:center;">
                        <p style="margin:0;font-family:Arial,sans-serif;font-size:12px;color:#9ca3af;">© {{current_year}} Votre Entreprise</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>`;

const initEditor = async () => {
    isLoading.value = true;
    await nextTick();

    // Initialize GrapesJS with native Block Manager
    editor.value = grapesjs.init({
        container: editorContainer.value,
        height: '100%',
        width: 'auto',
        fromElement: false,
        storageManager: false,
        noticeOnUnload: false,
        avoidInlineStyle: false,

        // Canvas configuration for centering
        canvas: {
            styles: [],
            scripts: []
        },

        deviceManager: {
            devices: [
                { name: 'Desktop', width: '' },
                { name: 'Tablet', width: '768px', widthMedia: '768px' },
                { name: 'Mobile', width: '375px', widthMedia: '375px' },
            ]
        },

        panels: { defaults: [] },

        // Block Manager with appendTo for native drag & drop
        blockManager: {
            appendTo: blocksPanel.value,
            blocks: [
                // ==========================================
                // STRUCTURE BLOCKS
                // ==========================================
                {
                    id: 'section-1col',
                    label: '1 Colonne',
                    category: 'Structure',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="8" width="32" height="32" rx="4" stroke="currentColor" stroke-width="2"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:#ffffff;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:20px;">Contenu ici</td>
                        </tr>
                    </table>`
                },
                {
                    id: 'section-2col',
                    label: '2 Colonnes',
                    category: 'Structure',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="8" width="16" height="32" rx="3" stroke="currentColor" stroke-width="2"/><rect x="26" y="8" width="16" height="32" rx="3" stroke="currentColor" stroke-width="2"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:#ffffff;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" width="50%" style="padding:20px;vertical-align:top;">Colonne 1</td>
                            <td data-gjs-type="cell" width="50%" style="padding:20px;vertical-align:top;">Colonne 2</td>
                        </tr>
                    </table>`
                },
                {
                    id: 'section-3col',
                    label: '3 Colonnes',
                    category: 'Structure',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="8" width="11" height="32" rx="2" stroke="currentColor" stroke-width="2"/><rect x="18" y="8" width="12" height="32" rx="2" stroke="currentColor" stroke-width="2"/><rect x="33" y="8" width="11" height="32" rx="2" stroke="currentColor" stroke-width="2"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:#ffffff;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" width="33%" style="padding:15px;vertical-align:top;">Col 1</td>
                            <td data-gjs-type="cell" width="34%" style="padding:15px;vertical-align:top;">Col 2</td>
                            <td data-gjs-type="cell" width="33%" style="padding:15px;vertical-align:top;">Col 3</td>
                        </tr>
                    </table>`
                },
                {
                    id: 'section-4col',
                    label: '4 Colonnes',
                    category: 'Structure',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="8" width="8" height="32" rx="2" stroke="currentColor" stroke-width="2"/><rect x="14" y="8" width="8" height="32" rx="2" stroke="currentColor" stroke-width="2"/><rect x="26" y="8" width="8" height="32" rx="2" stroke="currentColor" stroke-width="2"/><rect x="36" y="8" width="8" height="32" rx="2" stroke="currentColor" stroke-width="2"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:#ffffff;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" width="25%" style="padding:10px;vertical-align:top;">Col 1</td>
                            <td data-gjs-type="cell" width="25%" style="padding:10px;vertical-align:top;">Col 2</td>
                            <td data-gjs-type="cell" width="25%" style="padding:10px;vertical-align:top;">Col 3</td>
                            <td data-gjs-type="cell" width="25%" style="padding:10px;vertical-align:top;">Col 4</td>
                        </tr>
                    </table>`
                },
                {
                    id: 'section-sidebar-left',
                    label: 'Sidebar Gauche',
                    category: 'Structure',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="8" width="12" height="32" rx="3" stroke="currentColor" stroke-width="2"/><rect x="22" y="8" width="20" height="32" rx="3" stroke="currentColor" stroke-width="2"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:#ffffff;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" width="35%" style="padding:20px;vertical-align:top;background:#f9fafb;">Sidebar</td>
                            <td data-gjs-type="cell" width="65%" style="padding:20px;vertical-align:top;">Contenu principal</td>
                        </tr>
                    </table>`
                },
                {
                    id: 'section-sidebar-right',
                    label: 'Sidebar Droite',
                    category: 'Structure',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="8" width="20" height="32" rx="3" stroke="currentColor" stroke-width="2"/><rect x="30" y="8" width="12" height="32" rx="3" stroke="currentColor" stroke-width="2"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:#ffffff;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" width="65%" style="padding:20px;vertical-align:top;">Contenu principal</td>
                            <td data-gjs-type="cell" width="35%" style="padding:20px;vertical-align:top;background:#f9fafb;">Sidebar</td>
                        </tr>
                    </table>`
                },

                // ==========================================
                // CONTENT BLOCKS
                // ==========================================
                {
                    id: 'text',
                    label: 'Texte',
                    category: 'Contenu',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 14V10h28v4M24 10v28M18 38h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
                    content: `<p data-gjs-type="text" style="margin:0;padding:10px 0;font-family:Arial,sans-serif;font-size:16px;line-height:1.6;color:#374151;">Votre texte ici. Cliquez pour modifier.</p>`,
                    activate: true
                },
                {
                    id: 'heading',
                    label: 'Titre',
                    category: 'Contenu',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 12v24M36 12v24M12 24h24" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>`,
                    content: `<h2 data-gjs-type="text" style="margin:0;padding:10px 0;font-family:Arial,sans-serif;font-size:28px;font-weight:700;color:#111827;">Titre de section</h2>`,
                    activate: true
                },
                {
                    id: 'heading-subtitle',
                    label: 'Titre + Sous-titre',
                    category: 'Contenu',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 16h28M14 24h20M18 32h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<div style="text-align:center;padding:20px;">
                        <h2 data-gjs-type="text" style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:28px;font-weight:700;color:#111827;">Titre Principal</h2>
                        <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:16px;color:#6b7280;">Un sous-titre descriptif pour accompagner votre titre</p>
                    </div>`
                },
                {
                    id: 'button',
                    label: 'Bouton',
                    category: 'Contenu',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="16" width="32" height="16" rx="8" stroke="currentColor" stroke-width="2"/><path d="M16 24h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table cellpadding="0" cellspacing="0" data-gjs-type="table" style="margin:10px auto;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="background:#6366f1;border-radius:8px;">
                                <a href="#" data-gjs-type="link" style="display:inline-block;padding:14px 28px;font-family:Arial,sans-serif;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;">Cliquez ici</a>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'button-outline',
                    label: 'Bouton Outline',
                    category: 'Contenu',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="16" width="32" height="16" rx="8" stroke="currentColor" stroke-width="2" stroke-dasharray="4 2"/><path d="M16 24h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table cellpadding="0" cellspacing="0" data-gjs-type="table" style="margin:10px auto;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="border:2px solid #6366f1;border-radius:8px;background:transparent;">
                                <a href="#" data-gjs-type="link" style="display:inline-block;padding:12px 26px;font-family:Arial,sans-serif;font-size:16px;font-weight:600;color:#6366f1;text-decoration:none;">En savoir plus</a>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'button-dual',
                    label: 'Double Bouton',
                    category: 'Contenu',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="18" width="18" height="12" rx="6" stroke="currentColor" stroke-width="2"/><rect x="26" y="18" width="18" height="12" rx="6" stroke="currentColor" stroke-width="2"/></svg>`,
                    content: `<table cellpadding="0" cellspacing="0" data-gjs-type="table" style="margin:10px auto;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="background:#6366f1;border-radius:8px;">
                                <a href="#" data-gjs-type="link" style="display:inline-block;padding:14px 24px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">Principal</a>
                            </td>
                            <td style="width:12px;"></td>
                            <td data-gjs-type="cell" style="border:2px solid #e5e7eb;border-radius:8px;">
                                <a href="#" data-gjs-type="link" style="display:inline-block;padding:12px 22px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#374151;text-decoration:none;">Secondaire</a>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'image',
                    label: 'Image',
                    category: 'Contenu',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="10" width="32" height="28" rx="4" stroke="currentColor" stroke-width="2"/><circle cx="16" cy="18" r="3" stroke="currentColor" stroke-width="2"/><path d="M8 32l10-10 8 8 6-6 8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
                    content: `<img data-gjs-type="image" src="https://via.placeholder.com/600x300/e5e7eb/9ca3af?text=Image" alt="Image" style="max-width:100%;height:auto;display:block;margin:10px auto;border-radius:8px;" />`
                },
                {
                    id: 'image-text',
                    label: 'Image + Texte',
                    category: 'Contenu',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="10" width="16" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M26 12h16M26 18h12M26 24h8M6 30h36M6 36h24" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" width="40%" style="padding:20px;vertical-align:top;">
                                <img data-gjs-type="image" src="https://via.placeholder.com/200x150/e5e7eb/9ca3af?text=Image" alt="Image" style="max-width:100%;height:auto;display:block;border-radius:8px;" />
                            </td>
                            <td data-gjs-type="cell" width="60%" style="padding:20px;vertical-align:top;">
                                <h3 data-gjs-type="text" style="margin:0 0 12px;font-family:Arial,sans-serif;font-size:20px;font-weight:600;color:#111827;">Titre du bloc</h3>
                                <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:15px;line-height:1.6;color:#4b5563;">Description du contenu avec une image à gauche et du texte à droite.</p>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'divider',
                    label: 'Séparateur',
                    category: 'Contenu',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 24h32" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0;" />`
                },
                {
                    id: 'divider-fancy',
                    label: 'Séparateur Décoré',
                    category: 'Contenu',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 24h12M28 24h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="24" cy="24" r="3" stroke="currentColor" stroke-width="2"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;">
                        <tr>
                            <td style="border-top:1px solid #e5e7eb;"></td>
                            <td style="width:50px;text-align:center;color:#9ca3af;font-size:14px;">✦</td>
                            <td style="border-top:1px solid #e5e7eb;"></td>
                        </tr>
                    </table>`
                },
                {
                    id: 'spacer',
                    label: 'Espace',
                    category: 'Contenu',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M24 8v32M8 24h8M32 24h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<div data-gjs-type="default" style="height:30px;"></div>`
                },
                {
                    id: 'quote',
                    label: 'Citation',
                    category: 'Contenu',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 20c0-4.418 3.582-8 8-8v4c-2.209 0-4 1.791-4 4v8h8v8H12V20zM28 20c0-4.418 3.582-8 8-8v4c-2.209 0-4 1.791-4 4v8h8v8H28V20z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:20px auto;background:#f9fafb;border-left:4px solid #6366f1;border-radius:0 8px 8px 0;">
                        <tr>
                            <td style="padding:24px 32px;">
                                <p data-gjs-type="text" style="margin:0 0 12px;font-family:Georgia,serif;font-size:18px;font-style:italic;line-height:1.6;color:#374151;">"Votre citation inspirante ici. Une phrase qui marque les esprits."</p>
                                <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#6366f1;">— Auteur de la citation</p>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'list',
                    label: 'Liste',
                    category: 'Contenu',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="14" r="2" fill="currentColor"/><circle cx="12" cy="24" r="2" fill="currentColor"/><circle cx="12" cy="34" r="2" fill="currentColor"/><path d="M20 14h18M20 24h18M20 34h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:10px auto;">
                        <tr>
                            <td style="padding:8px 0;">
                                <table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="width:24px;vertical-align:top;padding-top:2px;color:#6366f1;font-weight:bold;">•</td>
                                        <td><p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:15px;line-height:1.5;color:#374151;">Premier élément de la liste</p></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;">
                                <table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="width:24px;vertical-align:top;padding-top:2px;color:#6366f1;font-weight:bold;">•</td>
                                        <td><p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:15px;line-height:1.5;color:#374151;">Deuxième élément de la liste</p></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;">
                                <table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="width:24px;vertical-align:top;padding-top:2px;color:#6366f1;font-weight:bold;">•</td>
                                        <td><p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:15px;line-height:1.5;color:#374151;">Troisième élément de la liste</p></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },

                // ==========================================
                // COMPLETE BLOCKS (SECTIONS)
                // ==========================================
                {
                    id: 'header',
                    label: 'En-tête Simple',
                    category: 'Sections',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="8" width="32" height="12" rx="3" stroke="currentColor" stroke-width="2"/><path d="M14 14h20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:#ffffff;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:24px;text-align:center;">
                                <img data-gjs-type="image" src="https://via.placeholder.com/150x50/6366f1/ffffff?text=LOGO" alt="Logo" style="max-width:150px;height:auto;" />
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'header-nav',
                    label: 'En-tête + Nav',
                    category: 'Sections',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="8" width="32" height="14" rx="3" stroke="currentColor" stroke-width="2"/><rect x="10" y="12" width="8" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/><path d="M24 15h4M30 15h4M36 15h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:#ffffff;border-bottom:1px solid #e5e7eb;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:20px 24px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="vertical-align:middle;">
                                            <img data-gjs-type="image" src="https://via.placeholder.com/120x40/6366f1/ffffff?text=LOGO" alt="Logo" style="max-width:120px;height:auto;" />
                                        </td>
                                        <td style="text-align:right;vertical-align:middle;">
                                            <a href="#" data-gjs-type="link" style="display:inline-block;padding:8px 14px;font-family:Arial,sans-serif;font-size:13px;color:#374151;text-decoration:none;">Accueil</a>
                                            <a href="#" data-gjs-type="link" style="display:inline-block;padding:8px 14px;font-family:Arial,sans-serif;font-size:13px;color:#374151;text-decoration:none;">Produits</a>
                                            <a href="#" data-gjs-type="link" style="display:inline-block;padding:8px 14px;font-family:Arial,sans-serif;font-size:13px;color:#374151;text-decoration:none;">Contact</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'hero',
                    label: 'Hero Simple',
                    category: 'Sections',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="8" width="32" height="32" rx="4" stroke="currentColor" stroke-width="2"/><path d="M14 18h20M18 24h12M20 30h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:60px 40px;text-align:center;">
                                <h1 data-gjs-type="text" style="margin:0 0 16px;font-family:Arial,sans-serif;font-size:36px;font-weight:700;color:#ffffff;">Titre Principal</h1>
                                <p data-gjs-type="text" style="margin:0 0 24px;font-family:Arial,sans-serif;font-size:18px;color:rgba(255,255,255,0.9);">Sous-titre accrocheur ici.</p>
                                <table cellpadding="0" cellspacing="0" data-gjs-type="table" style="margin:0 auto;">
                                    <tr data-gjs-type="row">
                                        <td data-gjs-type="cell" style="background:#ffffff;border-radius:8px;">
                                            <a href="#" data-gjs-type="link" style="display:inline-block;padding:14px 32px;font-family:Arial,sans-serif;font-size:16px;font-weight:600;color:#6366f1;text-decoration:none;">Découvrir</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'hero-image',
                    label: 'Hero + Image',
                    category: 'Sections',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="6" width="32" height="36" rx="4" stroke="currentColor" stroke-width="2"/><rect x="12" y="10" width="24" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M14 28h20M18 34h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:#ffffff;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:0;">
                                <img data-gjs-type="image" src="https://via.placeholder.com/600x300/6366f1/ffffff?text=Image+Hero" alt="Hero Image" style="width:100%;height:auto;display:block;" />
                            </td>
                        </tr>
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:40px;text-align:center;">
                                <h1 data-gjs-type="text" style="margin:0 0 16px;font-family:Arial,sans-serif;font-size:32px;font-weight:700;color:#111827;">Titre Accrocheur</h1>
                                <p data-gjs-type="text" style="margin:0 0 24px;font-family:Arial,sans-serif;font-size:16px;line-height:1.6;color:#4b5563;">Une description engageante pour accompagner votre image et inciter à l'action.</p>
                                <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                    <tr>
                                        <td style="background:#6366f1;border-radius:8px;">
                                            <a href="#" data-gjs-type="link" style="display:inline-block;padding:14px 32px;font-family:Arial,sans-serif;font-size:16px;font-weight:600;color:#ffffff;text-decoration:none;">Commencer</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'features',
                    label: 'Fonctionnalités',
                    category: 'Sections',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="10" width="10" height="10" rx="2" stroke="currentColor" stroke-width="2"/><rect x="19" y="10" width="10" height="10" rx="2" stroke="currentColor" stroke-width="2"/><rect x="32" y="10" width="10" height="10" rx="2" stroke="currentColor" stroke-width="2"/><path d="M11 26v10M24 26v10M37 26v10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:#ffffff;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:40px 20px;text-align:center;">
                                <h2 data-gjs-type="text" style="margin:0 0 32px;font-family:Arial,sans-serif;font-size:24px;font-weight:700;color:#111827;">Nos Fonctionnalités</h2>
                            </td>
                        </tr>
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:0 20px 40px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td width="33%" style="padding:0 10px;text-align:center;vertical-align:top;">
                                            <div style="width:56px;height:56px;margin:0 auto 16px;background:#eef2ff;border-radius:12px;line-height:56px;font-size:24px;">🚀</div>
                                            <h3 data-gjs-type="text" style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:16px;font-weight:600;color:#111827;">Rapide</h3>
                                            <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:14px;line-height:1.5;color:#6b7280;">Performance optimale garantie</p>
                                        </td>
                                        <td width="33%" style="padding:0 10px;text-align:center;vertical-align:top;">
                                            <div style="width:56px;height:56px;margin:0 auto 16px;background:#fef3c7;border-radius:12px;line-height:56px;font-size:24px;">⭐</div>
                                            <h3 data-gjs-type="text" style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:16px;font-weight:600;color:#111827;">Qualité</h3>
                                            <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:14px;line-height:1.5;color:#6b7280;">Standards élevés assurés</p>
                                        </td>
                                        <td width="33%" style="padding:0 10px;text-align:center;vertical-align:top;">
                                            <div style="width:56px;height:56px;margin:0 auto 16px;background:#dcfce7;border-radius:12px;line-height:56px;font-size:24px;">💪</div>
                                            <h3 data-gjs-type="text" style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:16px;font-weight:600;color:#111827;">Fiable</h3>
                                            <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:14px;line-height:1.5;color:#6b7280;">Service disponible 24/7</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },

                // ==========================================
                // PRODUCT / E-COMMERCE BLOCKS
                // ==========================================
                {
                    id: 'product-single',
                    label: 'Produit Simple',
                    category: 'E-commerce',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="10" y="6" width="28" height="28" rx="4" stroke="currentColor" stroke-width="2"/><path d="M14 38h20M18 42h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:280px;margin:20px auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:0;">
                                <img data-gjs-type="image" src="https://via.placeholder.com/280x200/f3f4f6/9ca3af?text=Produit" alt="Produit" style="width:100%;height:auto;display:block;" />
                            </td>
                        </tr>
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:20px;text-align:center;">
                                <h3 data-gjs-type="text" style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:16px;font-weight:600;color:#111827;">Nom du Produit</h3>
                                <p data-gjs-type="text" style="margin:0 0 12px;font-family:Arial,sans-serif;font-size:20px;font-weight:700;color:#6366f1;">49,99 €</p>
                                <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                    <tr>
                                        <td style="background:#6366f1;border-radius:6px;">
                                            <a href="#" data-gjs-type="link" style="display:inline-block;padding:10px 20px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">Acheter</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'product-horizontal',
                    label: 'Produit Horizontal',
                    category: 'E-commerce',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="12" width="16" height="24" rx="3" stroke="currentColor" stroke-width="2"/><path d="M26 16h16M26 22h12M26 28h8M26 34h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:20px auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" width="40%" style="padding:0;vertical-align:top;">
                                <img data-gjs-type="image" src="https://via.placeholder.com/240x200/f3f4f6/9ca3af?text=Produit" alt="Produit" style="width:100%;height:auto;display:block;" />
                            </td>
                            <td data-gjs-type="cell" width="60%" style="padding:24px;vertical-align:top;">
                                <h3 data-gjs-type="text" style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:18px;font-weight:600;color:#111827;">Nom du Produit Premium</h3>
                                <p data-gjs-type="text" style="margin:0 0 12px;font-family:Arial,sans-serif;font-size:14px;line-height:1.5;color:#6b7280;">Description courte du produit avec ses principales caractéristiques.</p>
                                <p data-gjs-type="text" style="margin:0 0 16px;font-family:Arial,sans-serif;font-size:24px;font-weight:700;color:#6366f1;">129,99 €</p>
                                <table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="background:#6366f1;border-radius:6px;">
                                            <a href="#" data-gjs-type="link" style="display:inline-block;padding:12px 24px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">Ajouter au panier</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'products-grid',
                    label: 'Grille Produits',
                    category: 'E-commerce',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="6" width="16" height="16" rx="3" stroke="currentColor" stroke-width="2"/><rect x="26" y="6" width="16" height="16" rx="3" stroke="currentColor" stroke-width="2"/><rect x="6" y="26" width="16" height="16" rx="3" stroke="currentColor" stroke-width="2"/><rect x="26" y="26" width="16" height="16" rx="3" stroke="currentColor" stroke-width="2"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:20px;text-align:center;">
                                <h2 data-gjs-type="text" style="margin:0 0 24px;font-family:Arial,sans-serif;font-size:24px;font-weight:700;color:#111827;">Nos Produits</h2>
                            </td>
                        </tr>
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:0 10px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td width="50%" style="padding:10px;vertical-align:top;">
                                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                                                <tr><td><img src="https://via.placeholder.com/280x180/f3f4f6/9ca3af?text=Produit+1" alt="Produit" style="width:100%;display:block;" /></td></tr>
                                                <tr><td style="padding:16px;text-align:center;">
                                                    <p data-gjs-type="text" style="margin:0 0 4px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#111827;">Produit 1</p>
                                                    <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:16px;font-weight:700;color:#6366f1;">29,99 €</p>
                                                </td></tr>
                                            </table>
                                        </td>
                                        <td width="50%" style="padding:10px;vertical-align:top;">
                                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                                                <tr><td><img src="https://via.placeholder.com/280x180/f3f4f6/9ca3af?text=Produit+2" alt="Produit" style="width:100%;display:block;" /></td></tr>
                                                <tr><td style="padding:16px;text-align:center;">
                                                    <p data-gjs-type="text" style="margin:0 0 4px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#111827;">Produit 2</p>
                                                    <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:16px;font-weight:700;color:#6366f1;">39,99 €</p>
                                                </td></tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'promo-banner',
                    label: 'Bannière Promo',
                    category: 'E-commerce',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="14" width="36" height="20" rx="4" stroke="currentColor" stroke-width="2"/><path d="M14 24h8M26 20h8M26 24h12M26 28h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:20px auto;background:linear-gradient(135deg,#dc2626 0%,#ef4444 100%);border-radius:12px;overflow:hidden;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:32px 40px;text-align:center;">
                                <p data-gjs-type="text" style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;text-transform:uppercase;letter-spacing:2px;color:rgba(255,255,255,0.8);">Offre Limitée</p>
                                <h2 data-gjs-type="text" style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:36px;font-weight:800;color:#ffffff;">-50% SUR TOUT</h2>
                                <p data-gjs-type="text" style="margin:0 0 20px;font-family:Arial,sans-serif;font-size:16px;color:rgba(255,255,255,0.9);">Utilisez le code PROMO50 à la caisse</p>
                                <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                    <tr>
                                        <td style="background:#ffffff;border-radius:8px;">
                                            <a href="#" data-gjs-type="link" style="display:inline-block;padding:14px 32px;font-family:Arial,sans-serif;font-size:16px;font-weight:700;color:#dc2626;text-decoration:none;">J'en profite !</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },

                // ==========================================
                // SOCIAL & FOOTER BLOCKS
                // ==========================================
                {
                    id: 'social-icons',
                    label: 'Réseaux Sociaux',
                    category: 'Social',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="24" r="6" stroke="currentColor" stroke-width="2"/><circle cx="24" cy="24" r="6" stroke="currentColor" stroke-width="2"/><circle cx="36" cy="24" r="6" stroke="currentColor" stroke-width="2"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:20px auto;">
                        <tr>
                            <td style="text-align:center;">
                                <a href="#" style="display:inline-block;margin:0 6px;"><img src="https://via.placeholder.com/40x40/1877f2/ffffff?text=f" alt="Facebook" style="width:40px;height:40px;border-radius:8px;" /></a>
                                <a href="#" style="display:inline-block;margin:0 6px;"><img src="https://via.placeholder.com/40x40/1da1f2/ffffff?text=t" alt="Twitter" style="width:40px;height:40px;border-radius:8px;" /></a>
                                <a href="#" style="display:inline-block;margin:0 6px;"><img src="https://via.placeholder.com/40x40/e4405f/ffffff?text=i" alt="Instagram" style="width:40px;height:40px;border-radius:8px;" /></a>
                                <a href="#" style="display:inline-block;margin:0 6px;"><img src="https://via.placeholder.com/40x40/0077b5/ffffff?text=in" alt="LinkedIn" style="width:40px;height:40px;border-radius:8px;" /></a>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'social-follow',
                    label: 'Suivez-nous',
                    category: 'Social',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="16" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 38h24M16 32h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:#f9fafb;border-radius:12px;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:32px 40px;text-align:center;">
                                <h3 data-gjs-type="text" style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:20px;font-weight:600;color:#111827;">Suivez-nous</h3>
                                <p data-gjs-type="text" style="margin:0 0 20px;font-family:Arial,sans-serif;font-size:14px;color:#6b7280;">Restez connecté sur nos réseaux sociaux</p>
                                <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                    <tr>
                                        <td style="padding:0 8px;"><a href="#" style="display:inline-block;width:44px;height:44px;background:#1877f2;border-radius:50%;text-align:center;line-height:44px;color:#fff;text-decoration:none;font-weight:bold;">f</a></td>
                                        <td style="padding:0 8px;"><a href="#" style="display:inline-block;width:44px;height:44px;background:#1da1f2;border-radius:50%;text-align:center;line-height:44px;color:#fff;text-decoration:none;font-weight:bold;">t</a></td>
                                        <td style="padding:0 8px;"><a href="#" style="display:inline-block;width:44px;height:44px;background:#e4405f;border-radius:50%;text-align:center;line-height:44px;color:#fff;text-decoration:none;font-weight:bold;">i</a></td>
                                        <td style="padding:0 8px;"><a href="#" style="display:inline-block;width:44px;height:44px;background:#ff0000;border-radius:50%;text-align:center;line-height:44px;color:#fff;text-decoration:none;font-weight:bold;">▶</a></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'footer',
                    label: 'Pied de page Simple',
                    category: 'Social',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="28" width="32" height="12" rx="3" stroke="currentColor" stroke-width="2"/><path d="M14 34h20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:#f3f4f6;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:32px 24px;text-align:center;">
                                <p data-gjs-type="text" style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:12px;color:#6b7280;">© {{current_year}} Votre Entreprise. Tous droits réservés.</p>
                                <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:12px;color:#9ca3af;">
                                    <a href="{{unsubscribe_url}}" data-gjs-type="link" style="color:#6b7280;">Se désinscrire</a> ·
                                    <a href="#" data-gjs-type="link" style="color:#6b7280;">Politique de confidentialité</a>
                                </p>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'footer-complete',
                    label: 'Pied de page Complet',
                    category: 'Social',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="24" width="36" height="18" rx="3" stroke="currentColor" stroke-width="2"/><path d="M12 30h10M12 36h6M26 30h10M26 36h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:0 auto;background:#1f2937;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:40px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td width="50%" style="vertical-align:top;padding-right:20px;">
                                            <img data-gjs-type="image" src="https://via.placeholder.com/120x40/6366f1/ffffff?text=LOGO" alt="Logo" style="max-width:120px;height:auto;margin-bottom:16px;" />
                                            <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:13px;line-height:1.6;color:#9ca3af;">Votre partenaire de confiance pour tous vos besoins. Nous sommes là pour vous accompagner.</p>
                                        </td>
                                        <td width="25%" style="vertical-align:top;padding:0 10px;">
                                            <h4 data-gjs-type="text" style="margin:0 0 16px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#ffffff;">Liens</h4>
                                            <p style="margin:0 0 8px;"><a href="#" data-gjs-type="link" style="font-family:Arial,sans-serif;font-size:13px;color:#9ca3af;text-decoration:none;">Accueil</a></p>
                                            <p style="margin:0 0 8px;"><a href="#" data-gjs-type="link" style="font-family:Arial,sans-serif;font-size:13px;color:#9ca3af;text-decoration:none;">À propos</a></p>
                                            <p style="margin:0;"><a href="#" data-gjs-type="link" style="font-family:Arial,sans-serif;font-size:13px;color:#9ca3af;text-decoration:none;">Contact</a></p>
                                        </td>
                                        <td width="25%" style="vertical-align:top;padding-left:10px;">
                                            <h4 data-gjs-type="text" style="margin:0 0 16px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#ffffff;">Contact</h4>
                                            <p data-gjs-type="text" style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:13px;color:#9ca3af;">contact@example.com</p>
                                            <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:13px;color:#9ca3af;">+225 01 02 03 04 05</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:20px 40px;border-top:1px solid #374151;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="font-family:Arial,sans-serif;font-size:12px;color:#6b7280;">© {{current_year}} Votre Entreprise</td>
                                        <td style="text-align:right;">
                                            <a href="{{unsubscribe_url}}" data-gjs-type="link" style="font-family:Arial,sans-serif;font-size:12px;color:#6b7280;text-decoration:none;">Se désinscrire</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },

                // ==========================================
                // SPECIAL BLOCKS
                // ==========================================
                {
                    id: 'testimonial',
                    label: 'Témoignage',
                    category: 'Special',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="14" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 28h24M16 34h16M20 40h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:20px auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:32px;text-align:center;">
                                <img data-gjs-type="image" src="https://via.placeholder.com/80x80/e5e7eb/9ca3af?text=Photo" alt="Photo" style="width:80px;height:80px;border-radius:50%;margin-bottom:16px;" />
                                <p data-gjs-type="text" style="margin:0 0 16px;font-family:Georgia,serif;font-size:16px;font-style:italic;line-height:1.6;color:#374151;">"Un service exceptionnel ! Je recommande vivement à tous ceux qui cherchent la qualité et le professionnalisme."</p>
                                <p data-gjs-type="text" style="margin:0 0 4px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#111827;">Marie Dupont</p>
                                <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:13px;color:#6b7280;">Directrice Marketing, Société XYZ</p>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'countdown',
                    label: 'Compte à Rebours',
                    category: 'Special',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="16" stroke="currentColor" stroke-width="2"/><path d="M24 14v10l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:20px auto;background:linear-gradient(135deg,#1e293b 0%,#334155 100%);border-radius:12px;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:32px;text-align:center;">
                                <p data-gjs-type="text" style="margin:0 0 16px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;text-transform:uppercase;letter-spacing:2px;color:#fbbf24;">L'offre expire dans</p>
                                <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                    <tr>
                                        <td style="padding:0 8px;text-align:center;">
                                            <div style="width:60px;height:60px;background:rgba(255,255,255,0.1);border-radius:8px;line-height:60px;font-family:Arial,sans-serif;font-size:28px;font-weight:700;color:#ffffff;">02</div>
                                            <p style="margin:8px 0 0;font-family:Arial,sans-serif;font-size:11px;color:#9ca3af;">JOURS</p>
                                        </td>
                                        <td style="padding:0 8px;text-align:center;">
                                            <div style="width:60px;height:60px;background:rgba(255,255,255,0.1);border-radius:8px;line-height:60px;font-family:Arial,sans-serif;font-size:28px;font-weight:700;color:#ffffff;">14</div>
                                            <p style="margin:8px 0 0;font-family:Arial,sans-serif;font-size:11px;color:#9ca3af;">HEURES</p>
                                        </td>
                                        <td style="padding:0 8px;text-align:center;">
                                            <div style="width:60px;height:60px;background:rgba(255,255,255,0.1);border-radius:8px;line-height:60px;font-family:Arial,sans-serif;font-size:28px;font-weight:700;color:#ffffff;">37</div>
                                            <p style="margin:8px 0 0;font-family:Arial,sans-serif;font-size:11px;color:#9ca3af;">MIN</p>
                                        </td>
                                        <td style="padding:0 8px;text-align:center;">
                                            <div style="width:60px;height:60px;background:rgba(255,255,255,0.1);border-radius:8px;line-height:60px;font-family:Arial,sans-serif;font-size:28px;font-weight:700;color:#ffffff;">52</div>
                                            <p style="margin:8px 0 0;font-family:Arial,sans-serif;font-size:11px;color:#9ca3af;">SEC</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'newsletter-signup',
                    label: 'Inscription Newsletter',
                    category: 'Special',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="16" width="32" height="16" rx="4" stroke="currentColor" stroke-width="2"/><path d="M14 24h12M32 24h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:20px auto;background:#eef2ff;border-radius:12px;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:40px;text-align:center;">
                                <h3 data-gjs-type="text" style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:22px;font-weight:700;color:#111827;">Restez informé !</h3>
                                <p data-gjs-type="text" style="margin:0 0 24px;font-family:Arial,sans-serif;font-size:15px;color:#4b5563;">Inscrivez-vous à notre newsletter pour recevoir nos dernières actualités.</p>
                                <table cellpadding="0" cellspacing="0" style="margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                                    <tr>
                                        <td style="padding:4px 4px 4px 16px;">
                                            <span style="font-family:Arial,sans-serif;font-size:14px;color:#9ca3af;">votre@email.com</span>
                                        </td>
                                        <td style="padding:4px;">
                                            <a href="#" data-gjs-type="link" style="display:inline-block;padding:12px 24px;background:#6366f1;border-radius:6px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">S'inscrire</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },
                {
                    id: 'event',
                    label: 'Événement',
                    category: 'Special',
                    media: `<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="12" width="32" height="28" rx="4" stroke="currentColor" stroke-width="2"/><path d="M8 20h32M16 8v8M32 8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
                    content: `<table width="100%" cellpadding="0" cellspacing="0" data-gjs-type="table" style="max-width:600px;margin:20px auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                        <tr data-gjs-type="row">
                            <td data-gjs-type="cell" style="padding:0;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td width="30%" style="background:#6366f1;padding:24px;text-align:center;vertical-align:middle;">
                                            <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:14px;font-weight:600;text-transform:uppercase;color:rgba(255,255,255,0.8);">Jan</p>
                                            <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:48px;font-weight:700;line-height:1;color:#ffffff;">25</p>
                                            <p data-gjs-type="text" style="margin:0;font-family:Arial,sans-serif;font-size:14px;color:rgba(255,255,255,0.8);">2025</p>
                                        </td>
                                        <td width="70%" style="padding:24px;">
                                            <h3 data-gjs-type="text" style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:18px;font-weight:600;color:#111827;">Conférence Annuelle</h3>
                                            <p data-gjs-type="text" style="margin:0 0 4px;font-family:Arial,sans-serif;font-size:14px;color:#6b7280;">📍 Centre de Congrès, Abidjan</p>
                                            <p data-gjs-type="text" style="margin:0 0 16px;font-family:Arial,sans-serif;font-size:14px;color:#6b7280;">🕐 09:00 - 17:00</p>
                                            <a href="#" data-gjs-type="link" style="display:inline-block;padding:10px 20px;background:#6366f1;border-radius:6px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">S'inscrire</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>`
                },
            ]
        },

        styleManager: {
            appendTo: stylesPanel.value,
            sectors: [
                { name: 'Dimension', open: true, buildProps: ['width', 'height', 'max-width', 'margin', 'padding'] },
                { name: 'Typographie', open: false, buildProps: ['font-family', 'font-size', 'font-weight', 'color', 'line-height', 'text-align'] },
                { name: 'Décoration', open: false, buildProps: ['background-color', 'border-radius', 'border'] },
            ]
        },

        traitManager: { appendTo: traitsPanel.value },
        layerManager: { appendTo: layersPanel.value },
        selectorManager: { appendTo: stylesPanel.value },
    });

    // Load content
    if (props.initialContent) {
        editor.value.setComponents(props.initialContent);
    } else {
        editor.value.setComponents(getDefaultTemplate());
    }

    // Event listeners
    editor.value.on('change:changesCount', () => {
        updateCode();
        emit('update:content', editor.value.getHtml());
        updateUndoRedo();
    });

    editor.value.on('load', () => {
        updateCode();
        updateUndoRedo();

        // Force canvas refresh for proper centering
        setTimeout(() => {
            editor.value.refresh();
        }, 100);
    });

    // Loading complete
    setTimeout(() => {
        isLoading.value = false;
    }, 300);
};

const getContent = () => {
    if (editor.value) {
        return { html: editor.value.getHtml(), css: editor.value.getCss() };
    }
    return { html: '', css: '' };
};

const setContent = (content) => {
    if (editor.value && content) {
        editor.value.setComponents(content);
    }
};

// Keyboard shortcuts
const handleKeydown = (e) => {
    if (e.key === 'Escape' && isFullscreen.value) {
        isFullscreen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    initEditor();
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
    if (editor.value) {
        editor.value.destroy();
    }
});

defineExpose({ getContent, setContent, editor });
</script>

<style>
/* ============================================
   BASE STYLES
   ============================================ */
.email-editor-wrapper {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: #f8fafc;
    position: relative;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.email-editor-wrapper.is-fullscreen {
    position: fixed;
    inset: 0;
    z-index: 9999;
}

/* Loading */
.editor-loading-overlay {
    position: absolute;
    inset: 0;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
}

.loading-content {
    text-align: center;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: #6366f1;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 16px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    color: #64748b;
    font-size: 14px;
}

/* ============================================
   TOOLBAR
   ============================================ */
.editor-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.toolbar-left, .toolbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.toolbar-group {
    display: flex;
    gap: 4px;
}

.toolbar-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    color: #64748b;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}

.toolbar-btn:hover:not(:disabled) {
    background: #f1f5f9;
    color: #0f172a;
}

.toolbar-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.toolbar-divider {
    width: 1px;
    height: 24px;
    background: #e2e8f0;
    margin: 0 4px;
}

/* Device Selector */
.device-selector {
    display: flex;
    background: #f1f5f9;
    border-radius: 10px;
    padding: 4px;
}

.device-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}

.device-btn.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.device-label {
    display: none;
}

@media (min-width: 768px) {
    .device-label { display: inline; }
}

/* View Toggle */
.view-toggle {
    display: flex;
    background: #f1f5f9;
    border-radius: 8px;
    padding: 3px;
}

.view-btn {
    padding: 7px 14px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}

.view-btn.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.preview-btn {
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #ffffff;
    background: #6366f1;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}

.preview-btn:hover {
    background: #4f46e5;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99,102,241,0.3);
}

/* ============================================
   EDITOR BODY
   ============================================ */
.editor-body {
    flex: 1;
    display: flex;
    overflow: hidden;
}

/* Sidebars */
.editor-sidebar {
    width: 280px;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    transition: width 0.2s;
    overflow: hidden;
}

.editor-sidebar.collapsed {
    width: 48px;
}

.left-sidebar {
    border-right: 1px solid #e2e8f0;
}

.right-sidebar {
    border-left: 1px solid #e2e8f0;
}

.sidebar-header {
    display: flex;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.sidebar-title {
    display: flex;
    align-items: center;
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
    flex: 1;
    margin: 0;
}

.collapse-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    color: #64748b;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}

.collapse-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.sidebar-content {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
}

/* ============================================
   BLOCKS PANEL (GrapesJS Native)
   ============================================ */
.blocks-panel {
    margin-bottom: 20px;
}

/* Variables */
.variables-section {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.section-subtitle {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 12px;
}

.variables-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.variable-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
    text-align: left;
    font-size: 12px;
    color: #374151;
}

.variable-item:hover {
    background: #eef2ff;
    border-color: #6366f1;
}

.variable-item code {
    font-size: 10px;
    color: #6366f1;
    background: #e0e7ff;
    padding: 2px 6px;
    border-radius: 4px;
}

/* ============================================
   CANVAS
   ============================================ */
.editor-canvas-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    padding: 24px;
    overflow: auto;
}

.canvas-container {
    width: 100%;
    max-width: 100%;
    height: 100%;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: max-width 0.3s;
}

.canvas-container.device-tablet {
    max-width: 768px;
}

.canvas-container.device-mobile {
    max-width: 375px;
}

.gjs-editor-container {
    height: 100%;
    width: 100%;
}

/* Code Editor */
.code-editor-wrapper {
    height: 100%;
    padding: 16px;
}

.code-panel {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: #1e293b;
    border-radius: 12px;
    overflow: hidden;
}

.code-panel-header {
    padding: 12px 16px;
    background: #0f172a;
}

.code-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.code-badge.html {
    background: #f97316;
    color: #fff;
}

.code-textarea {
    flex: 1;
    width: 100%;
    padding: 16px;
    background: transparent;
    border: none;
    color: #e2e8f0;
    font-family: 'Fira Code', monospace;
    font-size: 13px;
    line-height: 1.6;
    resize: none;
    outline: none;
}

/* ============================================
   RIGHT PANEL TABS
   ============================================ */
.panel-tabs {
    display: flex;
    gap: 4px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 16px;
}

.panel-tab {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 10px 8px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}

.panel-tab.active {
    background: #eef2ff;
    color: #6366f1;
}

.panel-tab:hover:not(.active) {
    background: #f1f5f9;
}

.panel-content {
    flex: 1;
    overflow-y: auto;
}

.gjs-panel-content {
    min-height: 200px;
}

/* ============================================
   PREVIEW MODAL
   ============================================ */
.preview-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.7);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 24px;
}

.preview-modal {
    width: 100%;
    max-width: 1200px;
    max-height: calc(100vh - 48px);
    background: #ffffff;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
}

.preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.preview-title {
    font-size: 16px;
    font-weight: 600;
    color: #0f172a;
}

.preview-devices {
    display: flex;
    gap: 4px;
    background: #e2e8f0;
    padding: 4px;
    border-radius: 8px;
}

.preview-device-btn {
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}

.preview-device-btn.active {
    background: #ffffff;
    color: #0f172a;
}

.preview-close {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    color: #64748b;
    background: transparent;
    border: none;
    cursor: pointer;
}

.preview-close:hover {
    background: #e2e8f0;
}

.preview-body {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: #e2e8f0;
    overflow: auto;
}

.preview-frame-container {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s;
}

.preview-frame-container.preview-desktop {
    width: 100%;
}

.preview-frame-container.preview-tablet {
    width: 768px;
}

.preview-frame-container.preview-mobile {
    width: 375px;
}

.preview-iframe {
    width: 100%;
    height: 600px;
    border: none;
}

/* Transitions */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.modal-enter-active, .modal-leave-active {
    transition: all 0.3s;
}

.modal-enter-from, .modal-leave-to {
    opacity: 0;
}

.modal-enter-from .preview-modal {
    transform: scale(0.95);
}

.rotate-180 {
    transform: rotate(180deg);
}

/* ============================================
   GRAPESJS COMPLETE LIGHT THEME OVERRIDE
   ============================================ */

/* GLOBAL RESET - Override ALL dark backgrounds from GrapesJS default theme */
.gjs-one-bg {
    background-color: #ffffff !important;
}

.gjs-two-color {
    color: #374151 !important;
}

.gjs-three-bg {
    background-color: #f8fafc !important;
}

.gjs-four-color,
.gjs-four-color-h:hover {
    color: #6366f1 !important;
}

/* Main editor and all containers */
.gjs-editor {
    background-color: #ffffff !important;
}

.gjs-pn-panel {
    background-color: #ffffff !important;
}

.gjs-pn-views,
.gjs-pn-views-container {
    background-color: #ffffff !important;
}

/* Canvas Area - CRITICAL - Remove all dark backgrounds */
.gjs-cv-canvas {
    background-color: #f1f5f9 !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
}

.gjs-cv-canvas__frames {
    background-color: #f1f5f9 !important;
    padding: 20px !important;
}

.gjs-frame-wrapper {
    background-color: #ffffff !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
    border-radius: 8px !important;
    margin: 0 auto !important;
}

.gjs-frame-wrapper__top,
.gjs-frame-wrapper__left,
.gjs-frame-wrapper__right,
.gjs-frame-wrapper__bottom {
    background-color: transparent !important;
}

.gjs-frame {
    background-color: #ffffff !important;
}

/* Block Manager - IMPROVED STYLING */
.gjs-blocks-c {
    background-color: transparent !important;
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
    padding: 0 !important;
}

.gjs-block-categories {
    background-color: transparent !important;
}

.gjs-block-category {
    background-color: transparent !important;
    border: none !important;
}

.gjs-block-category .gjs-caret-icon {
    color: #64748b !important;
}

.gjs-block-category .gjs-title {
    background-color: #f1f5f9 !important;
    color: #475569 !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    padding: 10px 14px !important;
    border-radius: 8px !important;
    border: none !important;
}

/* Block items - Better appearance */
.gjs-block {
    width: calc(50% - 4px) !important;
    min-height: 70px !important;
    padding: 12px 8px !important;
    background-color: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    cursor: grab !important;
    transition: all 0.15s ease !important;
    box-shadow: none !important;
    margin: 0 !important;
}

.gjs-block:hover {
    background-color: #eef2ff !important;
    border-color: #6366f1 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15) !important;
}

.gjs-block:active {
    cursor: grabbing !important;
    transform: translateY(0) !important;
}

/* Block icons - Proper sizing and color */
.gjs-block-svg {
    width: 28px !important;
    height: 28px !important;
    fill: none !important;
}

.gjs-block-svg svg {
    width: 28px !important;
    height: 28px !important;
    stroke: #6366f1 !important;
    fill: none !important;
}

.gjs-block svg {
    width: 28px !important;
    height: 28px !important;
    stroke: #6366f1 !important;
    color: #6366f1 !important;
}

.gjs-block:hover svg {
    stroke: #4f46e5 !important;
    color: #4f46e5 !important;
}

/* Block labels */
.gjs-block-label {
    font-size: 11px !important;
    font-weight: 500 !important;
    color: #374151 !important;
    text-align: center !important;
    line-height: 1.3 !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    max-width: 100% !important;
}

.gjs-block:hover .gjs-block-label {
    color: #4f46e5 !important;
}

/* Style Manager */
.gjs-sm-sectors,
.gjs-sm-sector {
    background-color: transparent !important;
    border: none !important;
}

.gjs-sm-sector-title {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
    font-weight: 600 !important;
    font-size: 12px !important;
    padding: 12px 14px !important;
    border-radius: 8px !important;
    border: none !important;
}

.gjs-sm-sector-caret {
    color: #64748b !important;
}

.gjs-sm-properties {
    padding: 8px 0 !important;
    background-color: transparent !important;
}

.gjs-sm-property {
    background-color: transparent !important;
}

.gjs-sm-label {
    color: #64748b !important;
    font-size: 12px !important;
    font-weight: 500 !important;
}

/* Input fields */
.gjs-field {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 6px !important;
}

.gjs-field:focus-within {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 2px rgba(99,102,241,0.1) !important;
}

.gjs-field input,
.gjs-field select,
.gjs-field textarea {
    background-color: transparent !important;
    color: #0f172a !important;
    border: none !important;
}

.gjs-field-arrows {
    background-color: #f1f5f9 !important;
    color: #64748b !important;
}

.gjs-field-arrow-u,
.gjs-field-arrow-d {
    border-top-color: #64748b !important;
    border-bottom-color: #64748b !important;
}

/* Selector Manager */
.gjs-clm-tags {
    background-color: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    padding: 8px !important;
}

.gjs-clm-tag {
    background-color: #e0e7ff !important;
    color: #4338ca !important;
    border-radius: 6px !important;
}

.gjs-clm-tag-close {
    color: #4338ca !important;
}

.gjs-clm-sels-info {
    color: #64748b !important;
}

/* Layers Panel - CRITICAL FIX */
.gjs-layers {
    background-color: transparent !important;
}

.gjs-layer {
    background-color: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 6px !important;
    margin-bottom: 4px !important;
}

.gjs-layer:hover {
    background-color: #eef2ff !important;
    border-color: #c7d2fe !important;
}

.gjs-layer.gjs-selected {
    background-color: #e0e7ff !important;
    border-color: #6366f1 !important;
}

/* Layer text - Force visible dark text */
.gjs-layer-title,
.gjs-layer-title-inn,
.gjs-layer-name,
.gjs-layer-title *,
.gjs-layer-title-c {
    color: #374151 !important;
    font-size: 12px !important;
}

.gjs-layer-caret,
.gjs-layer-vis,
.gjs-layer-move {
    color: #64748b !important;
}

.gjs-layer-children {
    background-color: transparent !important;
}

/* Nested layers */
.gjs-layer .gjs-layer {
    background-color: #ffffff !important;
}

/* Layer count badge */
.gjs-layer-count {
    background-color: #e2e8f0 !important;
    color: #475569 !important;
}

/* Traits Panel */
.gjs-trt-traits {
    background-color: transparent !important;
}

.gjs-trt-trait {
    background-color: transparent !important;
    border: none !important;
}

.gjs-trt-trait__wrp-title,
.gjs-label-wrp {
    color: #64748b !important;
    font-size: 12px !important;
}

/* Canvas toolbar */
.gjs-toolbar {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}

.gjs-toolbar-item {
    color: #64748b !important;
}

.gjs-toolbar-item:hover {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
}

/* Selection states */
.gjs-selected {
    outline: 2px solid #6366f1 !important;
    outline-offset: 0 !important;
}

.gjs-hovered {
    outline: 2px dashed #a5b4fc !important;
    outline-offset: 0 !important;
}

/* Dragging state */
.gjs-block.gjs-dragging {
    opacity: 0.7 !important;
    transform: scale(1.02) !important;
}

/* Badges */
.gjs-badge {
    background-color: #6366f1 !important;
    color: #ffffff !important;
    border-radius: 4px !important;
}

.gjs-badge__icon {
    color: #ffffff !important;
}

/* RTE Toolbar */
.gjs-rte-toolbar {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}

.gjs-rte-action {
    color: #64748b !important;
    border-radius: 4px !important;
}

.gjs-rte-action:hover,
.gjs-rte-active {
    background-color: #eef2ff !important;
    color: #6366f1 !important;
}

/* Buttons */
.gjs-btn-prim {
    background-color: #6366f1 !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 8px !important;
}

/* Radio/Checkbox */
.gjs-radio-item,
.gjs-checkbox {
    background-color: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    color: #64748b !important;
    border-radius: 6px !important;
}

.gjs-radio-item.active,
.gjs-radio-item-active,
.gjs-checkbox.gjs-active {
    background-color: #6366f1 !important;
    border-color: #6366f1 !important;
    color: #ffffff !important;
}

/* Modal dialogs */
.gjs-mdl-container {
    background-color: rgba(0, 0, 0, 0.5) !important;
}

.gjs-mdl-dialog {
    background-color: #ffffff !important;
    border-radius: 16px !important;
}

.gjs-mdl-header {
    background-color: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
    color: #0f172a !important;
}

.gjs-mdl-title {
    color: #0f172a !important;
}

.gjs-mdl-btn-close {
    color: #64748b !important;
}

.gjs-mdl-content {
    background-color: #ffffff !important;
    color: #374151 !important;
}

/* Color pickers */
.gjs-color-picker,
.sp-container {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
}

/* Drag hint */
.gjs-placeholder {
    border: 2px dashed #6366f1 !important;
    background-color: rgba(99, 102, 241, 0.1) !important;
}

.gjs-placeholder-int {
    background-color: rgba(99, 102, 241, 0.1) !important;
}

/* Resizer handles */
.gjs-resizer-h {
    border: 2px solid #6366f1 !important;
    background-color: #ffffff !important;
}

/* Highlighter */
.gjs-highlighter,
.gjs-highlighter-sel {
    outline: 2px solid #6366f1 !important;
}

/* Context menu */
.gjs-cm-list {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}

.gjs-cm-item {
    color: #374151 !important;
}

.gjs-cm-item:hover {
    background-color: #f1f5f9 !important;
}

/* Off canvas areas */
.gjs-off-prv {
    background-color: #f1f5f9 !important;
}

/* Scrollbars */
.sidebar-content::-webkit-scrollbar,
.panel-content::-webkit-scrollbar {
    width: 6px;
}

.sidebar-content::-webkit-scrollbar-track,
.panel-content::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-content::-webkit-scrollbar-thumb,
.panel-content::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.sidebar-content::-webkit-scrollbar-thumb:hover,
.panel-content::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
