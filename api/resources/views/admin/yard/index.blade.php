@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Isotank Yard Positioning</h2>
        <div>
            @if(auth()->user()->role === 'yard_operator')
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadLayoutModal">
                <i class="bi bi-upload"></i> Upload Layout (Excel)
            </button>
            @endif

            <button class="btn btn-secondary" onclick="loadYardData()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Unplaced Isotanks Sidebar -->
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Unplaced (SMGRS)</h5>
                    <small class="text-muted">Drag to yard slot</small>
                </div>
                <div class="card-body p-2 overflow-auto" style="max-height: 80vh;">
                    <div id="unplaced-list" class="d-flex flex-column gap-2">
                        <!-- Unplaced items will be rendered here -->
                        <div class="text-center text-muted py-4">Loading...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yard Map -->
        <div class="col-md-9">
            <div id="yard-main-card" class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Yard Map</h5>
                    <div class="d-flex gap-3 align-items-center flex-wrap">
                        <!-- Search Box -->
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="yard-search-input" class="form-control" placeholder="Find Isotank..." onkeyup="handleYardSearch(this.value)">
                        </div>

                        <div class="border-start mx-2 d-none d-md-block"></div>

                        <div class="d-flex align-items-center"><span class="badge bg-primary me-2">Occupied</span> Placed</div>
                        <div class="ms-3 text-muted small"><i class="bi bi-info-circle"></i> Slot color indicates Zone</div>
                    </div>
                </div>
                <!-- STATISTICS BAR -->
                <div id="yard-stats" class="d-flex gap-3 overflow-auto px-3 py-2 border-bottom bg-white small text-nowrap align-items-center" style="scrollbar-width: thin;">
                     <span class="text-muted fst-italic">Loading stats...</span>
                </div>

                <!-- FLOATING CONTROLS (Fixed Overlay) -->
                <div class="position-absolute bottom-0 end-0 m-4 d-flex flex-column gap-2" style="z-index: 1000; pointer-events: auto;">
                    <button class="btn btn-light shadow border" onclick="toggleFullScreen()" title="Full Screen">
                        <i class="bi bi-arrows-fullscreen"></i>
                    </button>
                    <div class="btn-group-vertical shadow border bg-white rounded">
                        <button class="btn btn-light py-2 fw-bold" onclick="updateZoom(currentZoom + 0.1)" title="Zoom In">+</button>
                        <button class="btn btn-light py-2" onclick="updateZoom(1.0)" title="Reset 1:1">⟲</button>
                        <button class="btn btn-light py-2 fw-bold" onclick="updateZoom(currentZoom - 0.1)" title="Zoom Out">-</button>
                    </div>
                </div>

                <div id="yard-view-wrapper" class="card-body p-0 overflow-auto bg-light position-relative" style="max-height: 80vh;">
                    <div id="yard-container" class="p-3">
                        <!-- Areas -> Blocks -> Rows -> Tiers rendered here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadLayoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="uploadLayoutForm">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Yard Layout (Excel)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Upload an Excel file (.xlsx or .xls) with your yard layout.<br>
                        - Mark slots with <strong>"X"</strong><br>
                        - Use merged cells for labels<br>
                        - Colors and borders will be preserved<br>
                        <strong>This will REPLACE the current layout.</strong>
                    </p>
                    <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<style>
    /* Premium Design System */
    :root {
        --primary-gradient: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
        --slot-empty-bg: #f8f9fa;
        --slot-border: #e2e8f0;
        --area-bg: #ffffff;
        --area-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* MAP LAYOUT - ABSOLUTE MODE */
    #yard-container {
        /* Absolute positioning mode - dimensions set by JS */
        overflow: auto;
    }

    .yard-slot {
        /* Position set by JS - absolute */
        background: var(--slot-empty-bg);
        border: 1px solid var(--slot-border);
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
        color: #cbd5e1;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .yard-slot:hover { border-color: #94a3b8; transform: scale(1.1); z-index: 5; }
    .yard-slot.active { }
    .yard-slot.drag-over { background: #dcfce7; border: 2px dashed #16a34a; transform: scale(1.05); }

    /* ISOTANK CARD */
    .isotank-card {
        width: 95%;
        height: 95%;
        background: var(--primary-gradient);
        color: white;
        border-radius: 3px;
        padding: 2px;
        font-size: 0.6rem;
        cursor: grab;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        z-index: 10;
        overflow: hidden;
    }
    .isotank-card:active { cursor: grabbing; }
    .isotank-card .tank-number { font-weight: 700; display: block; font-size: 0.55rem; line-height: 1.1;}
    .isotank-card .cargo { font-size: 0.5rem; opacity: 0.9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Unplaced */
    .unplaced-item {
        background: white; border: 1px solid #e2e8f0; padding: 10px; border-radius: 6px;
        cursor: grab; border-left: 4px solid #3b82f6; margin-bottom: 8px; font-size: 0.8rem;
    }
    .unplaced-item:hover { transform: translateX(4px); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

    /* ZONES Special treatment: Horizontal scrolling if huge */
    .area-ZONA_1 .area-content, .area-ZONA_2 .area-content, .area-ZONA_3 .area-content {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }
    #yard-main-card:fullscreen {
        width: 100vw !important;
        height: 100vh !important;
        z-index: 9999;
        overflow: hidden; /* Prevent body scroll showing up */
    }
    #yard-main-card:fullscreen .card-body {
        max-height: none !important;
        height: calc(100vh - 60px) !important; /* Approx header height */
    }

    /* PANNING (Hand Tool) Styles */
    #yard-view-wrapper {
        cursor: grab; /* Default cursor for the map area */
    }
    #yard-view-wrapper.is-panning {
        cursor: grabbing !important;
    }
    #yard-view-wrapper.is-panning .yard-slot, 
    #yard-view-wrapper.is-panning .isotank-card {
        pointer-events: none; /* Disable hover/clicks while panning for performance/smoothness */
    }
    /* Highlight Search */
    .isotank-card.highlight-search, .unplaced-item.highlight-search {
        box-shadow: 0 0 0 4px #ffeb3b, 0 0 10px rgba(0,0,0,0.5) !important;
        transform: scale(1.2) !important;
        z-index: 100 !important;
        border-color: #f59f00 !important;
        transition: all 0.3s ease;
    }
</style>

<script>
    const API_BASE = "{{ route('yard.index') }}";
    const ROLE_CAN_EDIT = {{ in_array(auth()->user()->role, ['yard_operator', 'admin']) ? 'true' : 'false' }};
    const CSRF_TOKEN = "{{ csrf_token() }}";
    
    let clipboard = null; // { id: '...', element: DOMElement, data: {number, cargo} }

    function handleYardSearch(query) {
        query = query.toUpperCase().trim();
        
        // Remove previous highlights
        document.querySelectorAll('.highlight-search').forEach(el => {
            el.classList.remove('highlight-search');
        });

        if (!query) return;

        // Find matches (Yard + Unplaced)
        const cards = document.querySelectorAll('.isotank-card, .unplaced-item');
        let firstMatch = null;

        cards.forEach(card => {
            const num = card.dataset.isotankNumber;
            if (num && num.toUpperCase().includes(query)) {
                card.classList.add('highlight-search');
                if (!firstMatch) firstMatch = card;
            }
        });

        // Scroll to first match
        if (firstMatch) {
            firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadYardData();
        if (ROLE_CAN_EDIT) {
             document.getElementById('uploadLayoutForm').addEventListener('submit', handleUpload);
             document.addEventListener('keydown', handleKeyboardShortcuts);
             document.addEventListener('click', handleSelection);
        }
    });
    
    // --- KEYBOARD & SELECTION LOGIC ---
    let selectedEl = null;

    function handleSelection(e) {
        // Deselect if clicking outside
        if (!e.target.closest('.isotank-card') && !e.target.closest('.unplaced-item') && !e.target.closest('.yard-slot')) {
            clearSelection();
            return;
        }

        // Select Isotank Card
        const card = e.target.closest('.isotank-card') || e.target.closest('.unplaced-item');
        if (card) {
            clearSelection();
            selectedEl = card;
            selectedEl.style.outline = '3px solid #facc15'; // Yellow outline for selection
            selectedEl.style.zIndex = '100';
            return;
        }

        // Select Empty Slot (for pasting destination) -- optional, but click to focus slot helps understand target
        const slot = e.target.closest('.yard-slot');
        if (slot) {
            clearSelection();
            selectedEl = slot;
            selectedEl.style.outline = '3px solid #22c55e'; // Green outline for target
            showToast("Destination Slot Selected");
        }
    }

    function clearSelection() {
        if (selectedEl) {
            selectedEl.style.outline = '';
            selectedEl.style.zIndex = '';
            selectedEl = null;
        }
    }

    function handleKeyboardShortcuts(e) {
        // Ignore if focus is on an input or textarea (allow native copy/paste there)
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

        const key = e.key.toLowerCase();

        // CTRL+X (Cut)
        if (e.ctrlKey && key === 'x') {
            if (selectedEl && (selectedEl.classList.contains('isotank-card') || selectedEl.classList.contains('unplaced-item'))) {
                e.preventDefault();
                console.log('CUT Triggered for:', selectedEl);
                performCut(selectedEl);
            }
        }
        
        // CTRL+V (Paste)
        if (e.ctrlKey && key === 'v') {
            if (clipboard && selectedEl && selectedEl.classList.contains('yard-slot')) {
                 // Check if slot is effectively empty (no isotank card)
                 if (!selectedEl.querySelector('.isotank-card')) {
                     e.preventDefault();
                     console.log('PASTE Triggered to:', selectedEl);
                     performPaste(selectedEl);
                 } else {
                     showToast("Slot occupied!");
                 }
            } else if (!clipboard) {
                showToast("Clipboard empty - Cut an isotank first");
            } else if (!selectedEl) {
                showToast("Select a destination slot first");
            }
        }
    }

    function performCut(element) {
        const id = element.dataset.isotankId;
        
        // Retrieve data from dataset (now available on both card types)
        const number = element.dataset.isotankNumber || element.querySelector('.tank-number')?.textContent || element.querySelector('.fw-bold')?.textContent;
        const cargo = element.dataset.cargo || element.querySelector('.cargo')?.textContent || element.querySelector('.text-muted')?.textContent;
        const fillingStatus = element.dataset.fillingStatus;
        const activity = element.dataset.activity;

        clipboard = { id, element, data: { number, cargo, fillingStatus, activity } };
        
        // Visual feedback for cut
        element.style.opacity = '0.4';
        element.style.border = '2px dashed red';
        
        // Toast or specific UI
        showToast(`Isotank ${number} cut to clipboard`);
        clearSelection();
    }

    async function performPaste(targetSlot) {
        if (!clipboard) return;

        const { id, element: originalEl, data } = clipboard;
        // OLD: const targetSlotCode = targetSlot.dataset.slotCode;
        const targetCellId = targetSlot.dataset.slotId; // Check if slotId or yardCellId

        // 1. Optimistic UI Paste
        const cardToPlace = createTankCard({ 
            id: id, 
            isotank_number: data.number, 
            current_cargo: data.cargo,
            filling_status: data.fillingStatus,
            activity: data.activity
        });
        
        targetSlot.innerHTML = '';
        targetSlot.removeAttribute('title');
        targetSlot.appendChild(cardToPlace);
        
        // 2. Remove original
        const originalParent = originalEl.parentElement;
        originalEl.remove();
        
        if (originalParent.classList.contains('yard-slot') && originalParent.children.length === 0) {
             // NEW: restore cell label if empty
             originalParent.innerHTML = `<div style="text-align:center;line-height:1.2;">
                    <div style="font-size:10px; color:#94a3b8;">Empty</div>
                </div>`;
        }

        // 3. Clear clipboard
        clipboard = null;
        clearSelection();
        showToast(`Moving ${data.number}...`);

        // 4. API Call
        try {
            const formData = new FormData();
            formData.append('isotank_id', id);
            // OLD: formData.append('slot_code', targetSlotCode);
            formData.append('slot_id', targetCellId);
            
            const res = await fetch("{{ route('yard.move') }}", { 
                method: 'POST', 
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }, 
                body: formData 
            });
            const resData = await res.json();
            
            if (res.ok) {
                loadYardData(true); 
                showToast(`Moved successfully`);
            } else { 
                throw new Error(resData.error || 'Move failed');
            }
        } catch (err) { 
            alert('Move Error: ' + err.message); 
            loadYardData();
        }
    }

    function showToast(msg) {
        // Simple toast implementation
        const div = document.createElement('div');
        div.style.position = 'fixed';
        div.style.bottom = '20px';
        div.style.right = '20px';
        div.style.background = '#333';
        div.style.color = '#fff';
        div.style.padding = '10px 20px';
        div.style.borderRadius = '5px';
        div.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
        div.style.zIndex = '9999';
        div.textContent = msg;
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 3000);
    }

    // --- END KEYBOARD LOGIC ---

    async function handleUpload(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        // Show loading state
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Uploading...';
        
        try {
            const res = await fetch("{{ route('yard.layout.upload') }}", {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            // Check if response is JSON
            const contentType = res.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response. Check server logs.');
            }
            
            const data = await res.json();
            
            if (res.ok) { 
                alert(`Layout uploaded successfully! ${data.cells_count} cells imported.`); 
                location.reload(); 
            } else { 
                throw new Error(data.error || 'Upload failed'); 
            }
        } catch (err) { 
            console.error('Upload error:', err);
            alert('Upload Error: ' + err.message); 
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }

    async function loadYardData(isQuiet = false) {
        const yardContainer = document.getElementById('yard-container');
        if (!isQuiet) {
            yardContainer.innerHTML = '<div class="text-center p-5 display-6" style="grid-column: 1/-1;">Loading Yard Map...</div>';
        }
        
        try {
            const [layoutRes, posRes] = await Promise.all([
                fetch("{{ route('yard.layout') }}"),
                fetch("{{ route('yard.positions') }}")
            ]);
            const slots = await layoutRes.json();
            const positionsData = await posRes.json();
            
            renderYard(slots, positionsData.placed);
            renderUnplaced(positionsData.unplaced);
            renderStats(positionsData.stats, positionsData.filling_status_stats);
        } catch (err) {
            console.error(err);
            if (!isQuiet) {
                yardContainer.innerHTML = `<div class="alert alert-danger" style="grid-column: 1/-1;">Failed to load data: ${err.message}</div>`;
            }
        }
    }

    function renderUnplaced(tanks) {
        const container = document.getElementById('unplaced-list');
        container.innerHTML = '';
        if (tanks.length === 0) {
            container.innerHTML = '<div class="text-muted text-center small">No unplaced isotanks in SMGRS</div>';
            return;
        }
        tanks.forEach(tank => {
            const el = document.createElement('div');
            el.className = 'unplaced-item';
            el.draggable = ROLE_CAN_EDIT;
            el.dataset.isotankId = tank.id;
            
            // Store data for Drag & Drop reconstruction
            el.dataset.isotankNumber = tank.isotank_number;
            el.dataset.cargo = tank.current_cargo;
            el.dataset.fillingStatus = tank.filling_status;
            el.dataset.activity = tank.activity;

            // Tooltip
            el.title = `No Isotank: ${tank.isotank_number}\nStatus: ${tank.filling_status}\nActivity: ${tank.activity}`;

            el.innerHTML = `<div class="fw-bold">${tank.isotank_number}</div><div class="small text-muted">${tank.current_cargo}</div>`;
            if (ROLE_CAN_EDIT) {
                el.addEventListener('dragstart', handleDragStart);
                el.addEventListener('dragend', handleDragEnd);
            }
            container.appendChild(el);
        });
    }

    // --- ZOOM LOGIC ---
    let currentZoom = 1;
    const MIN_ZOOM = 0.5;
    const MAX_ZOOM = 3.0;
    let baseCanvasWidth = 0;
    let baseCanvasHeight = 0;

    function handleZoom(e) {
        if (e.ctrlKey) {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            updateZoom(currentZoom + delta);
        }
    }

    function updateZoom(newZoom) {
        currentZoom = Math.min(Math.max(newZoom, MIN_ZOOM), MAX_ZOOM);
        
        const stage = document.getElementById('yard-stage');
        const container = document.getElementById('yard-container');
        
        if (stage && container) {
            stage.style.transform = `scale(${currentZoom})`;
            container.style.width = (baseCanvasWidth * currentZoom) + 'px';
            container.style.height = (baseCanvasHeight * currentZoom) + 'px';
            
            // Update UI/Toast if needed or just console
            // const percent = Math.round(currentZoom * 100);
            // showToast(`Zoom: ${percent}%`);
        }
    }

    // Initialize Zoom Listener
    document.addEventListener('DOMContentLoaded', () => {
        // Use ID to ensure we target the Yard Map container, NOT the sidebar
        const wrapper = document.getElementById('yard-view-wrapper'); 
        
        if (wrapper) {
            // Zoom Listener
            wrapper.addEventListener('wheel', handleZoom, { passive: false });
            
            // --- PANNING (Hand Tool) LOGIC ---
            let isDown = false;
            let startX, startY, scrollLeft, scrollTop;
            let hasMoved = false;

            wrapper.addEventListener('mousedown', (e) => {
                // Ignore interactive elements (Cards, buttons)
                // Note: We DO want to allow dragging starting from empty slots, so we don't exclude them here.
                if (e.target.closest('.isotank-card') || 
                    e.target.closest('.unplaced-item') || 
                    e.target.tagName === 'BUTTON') {
                    return;
                }
                
                isDown = true;
                hasMoved = false;
                // DO NOT add 'is-panning' yet. Wait for movement.
                
                startX = e.pageX;
                startY = e.pageY;
                scrollLeft = wrapper.scrollLeft;
                scrollTop = wrapper.scrollTop;
            });

            const stopPanning = () => {
                isDown = false;
                // Remove class if it was added
                if (wrapper.classList.contains('is-panning')) {
                    wrapper.classList.remove('is-panning');
                }
            };

            wrapper.addEventListener('mouseleave', stopPanning);
            wrapper.addEventListener('mouseup', stopPanning);

            wrapper.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                
                const x = e.pageX;
                const y = e.pageY;
                const walkX = (x - startX);
                const walkY = (y - startY);

                // Threshold to detect actual drag
                if (Math.abs(walkX) > 5 || Math.abs(walkY) > 5) {
                    hasMoved = true;
                    // NOW we are dragging, disable pointer events on children for performance
                    if (!wrapper.classList.contains('is-panning')) {
                        wrapper.classList.add('is-panning'); 
                    }
                    
                    e.preventDefault();
                    wrapper.scrollLeft = scrollLeft - walkX;
                    wrapper.scrollTop = scrollTop - walkY;
                }
            });
            // --- END PANNING LOGIC ---
        }
    });
    // --- END ZOOM LOGIC ---

    function toggleFullScreen() {
        const elem = document.getElementById('yard-main-card');
        if (!document.fullscreenElement) {
            elem.requestFullscreen().catch(err => {
                console.error(`Error attempting to enable full-screen mode: ${err.message}`);
                alert('Fullscreen blocked by browser. Please enable permissions.');
            });
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }

    function renderYard(slots, placedPositions) {
        const container = document.getElementById('yard-container');
        // Setup Stage if not exists
        let stage = document.getElementById('yard-stage');
        if (!stage) {
            container.innerHTML = ''; // Start fresh
            stage = document.createElement('div');
            stage.id = 'yard-stage';
            stage.style.position = 'absolute';
            stage.style.top = '0';
            stage.style.left = '0';
            stage.style.transformOrigin = '0 0';
            container.appendChild(stage);
        } else {
            stage.innerHTML = '';
        }
        
        const CELL_WIDTH = 70;
        const CELL_HEIGHT = 70;
        const PADDING = 20;
        
        if (!slots || slots.length === 0) {
            stage.innerHTML = '<div class="p-5 text-muted">No layout found. Please upload an Excel file.</div>';
            return;
        }

        // Calculate canvas size
        let maxCol = 0, maxRow = 0;
        slots.forEach(slot => {
            maxCol = Math.max(maxCol, slot.col_index);
            maxRow = Math.max(maxRow, slot.row_index);
        });
        
        baseCanvasWidth = (maxCol * CELL_WIDTH) + (PADDING * 2);
        baseCanvasHeight = (maxRow * CELL_HEIGHT) + (PADDING * 2);
        
        container.style.position = 'relative';
        container.style.background = '#ffffff';
        container.style.width = baseCanvasWidth + 'px';
        container.style.height = baseCanvasHeight + 'px';
        
        // Initial Zoom Apply
        updateZoom(currentZoom);
        
        // Render SLOTS
        slots.forEach(slot => {
            const slotEl = document.createElement('div');
            // Use 'yard-slot' class for interactivity
            slotEl.className = 'yard-slot active';
            slotEl.dataset.slotId = slot.id; // New ID
            slotEl.title = `Area: ${slot.area_label} (R${slot.row_index}-C${slot.col_index})`;
            
            // Position
            const x = (slot.col_index - 1) * CELL_WIDTH + PADDING;
            const y = (slot.row_index - 1) * CELL_HEIGHT + PADDING;
            
            slotEl.style.position = 'absolute';
            slotEl.style.left = x + 'px';
            slotEl.style.top = y + 'px';
            slotEl.style.width = (CELL_WIDTH - 4) + 'px'; // Gap
            slotEl.style.height = (CELL_HEIGHT - 4) + 'px'; // Gap
            
            // Visuals
            // Rule: Use Excel BG color if available and empty, otherwise default.
            // If occupied, the card covers it, so this mostly affects empty slots.
            if (slot.bg_color) {
               slotEl.style.backgroundColor = slot.bg_color;
               // Check contrast if needed, but for now simple
               slotEl.style.border = '1px solid rgba(0,0,0,0.1)';
            } else {
               slotEl.style.backgroundColor = '#f1f5f9'; // Default slot bg
               slotEl.style.border = '1px solid #cbd5e1';
            }
            
            // Find if occupied
            // Note: API returns 'slot_id' in mapped positions, but check if we need to adjust strict check
            const position = placedPositions.find(p => 
                p.isotank && (p.slot_id == slot.id || p.yard_cell_id == slot.id)
            );
            
            if (position) {
                // Remove title from slot so tank card title takes precedence
                slotEl.removeAttribute('title');
                slotEl.appendChild(createTankCard(position.isotank));
            } else {
                // Determine text color based on BG brightness? simplified:
                // If BG exists, maybe white or black text? Defaulting to standard logic.
                const textColor = slot.bg_color ? 'rgba(0,0,0,0.6)' : '#64748b';
                
                // Show Area Label + Raw coords if empty
                slotEl.innerHTML = `<div style="text-align:center;line-height:1.2;">
                    <div style="font-weight:bold; color:${textColor}; font-size:10px;">${slot.area_label || ''}</div>
                    <div style="font-size:8px; color:#94a3b8;">${slot.row_index}-${slot.col_index}</div>
                </div>`;
            }

            if (ROLE_CAN_EDIT) {
                slotEl.addEventListener('dragover', handleDragOver);
                slotEl.addEventListener('dragleave', handleDragLeave);
                slotEl.addEventListener('drop', (e) => handleDrop(e, slot));
            }
            
            stage.appendChild(slotEl);
        });
    }

    function renderStats(stats, fillingStatusStats) {
        const el = document.getElementById('yard-stats');
        if (!stats || Object.keys(stats).length === 0) {
            el.innerHTML = '<span class="text-muted">No stats available</span>';
            return;
        }

        let html = '<div class="fw-bold me-2">Area Usage:</div>';
        for (const [area, data] of Object.entries(stats)) {
            // Calculate percentage
            const pct = data.total > 0 ? Math.round((data.occupied / data.total) * 100) : 0;
            // Color code based on usage
            let badgeClass = 'bg-success';
            if (pct > 80) badgeClass = 'bg-danger';
            else if (pct > 50) badgeClass = 'bg-warning text-dark';
            else if (pct === 0) badgeClass = 'bg-secondary';
            
            html += `
                <div class="d-flex align-items-center border rounded px-2 py-1 bg-light">
                    <span class="fw-bold me-2">${area}:</span>
                    <span class="badge ${badgeClass} me-1">${data.occupied}/${data.total}</span>
                    <span class="small text-muted">(${pct}%)</span>
                </div>
            `;
        }
        
        // Add Filling Status Stats
        if (fillingStatusStats && Object.keys(fillingStatusStats).length > 0) {
            html += '<div class="border-start mx-3"></div>';
            html += '<div class="fw-bold me-2">Filling Status:</div>';
            
            const statusColors = {
                'ongoing_inspection': '#9E9E9E',
                'ready_to_fill': '#4CAF50',
                'filled': '#2196F3',
                'under_maintenance': '#FF9800',
                'waiting_team_calibration': '#FFC107',
                'class_survey': '#9C27B0',
                'no_status': '#9E9E9E'
            };
            
            for (const [code, data] of Object.entries(fillingStatusStats)) {
                const color = statusColors[code] || '#6c757d';
                html += `
                    <div class="d-flex align-items-center border rounded px-2 py-1 bg-light">
                        <span class="badge me-1" style="background-color: ${color};">${data.count}</span>
                        <span class="small">${data.description}</span>
                    </div>
                `;
            }
        }
        
        el.innerHTML = html;
    }

    function createTankCard(tank) {
        const div = document.createElement('div');
        div.className = 'isotank-card';
        div.draggable = ROLE_CAN_EDIT;
        div.dataset.isotankId = tank.id;
        
        // Store data
        div.dataset.isotankNumber = tank.isotank_number;
        div.dataset.cargo = tank.current_cargo;
        div.dataset.fillingStatus = tank.filling_status || 'Unknown';
        div.dataset.activity = tank.activity || 'STORAGE';


        // Determine Color Class based on filling_status_code
        let statusClass = 'status-filled'; // Default
        let bgColor = null; // Direct color override
        
        const act = (tank.activity || '').toLowerCase();
        const fillingStatusCode = tank.filling_status_code || '';
        
        // Priority 1: Filling Status Code (New System)
        if (fillingStatusCode) {
            switch(fillingStatusCode) {
                case 'ongoing_inspection':
                    bgColor = 'linear-gradient(135deg, #9E9E9E 0%, #BDBDBD 100%)'; // Grey
                    break;
                case 'ready_to_fill':
                    bgColor = 'linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%)'; // Green
                    break;
                case 'filled':
                    bgColor = 'linear-gradient(135deg, #2196F3 0%, #42A5F5 100%)'; // Blue
                    break;
                case 'under_maintenance':
                    bgColor = 'linear-gradient(135deg, #FF9800 0%, #FFA726 100%)'; // Orange
                    break;
                case 'waiting_team_calibration':
                    bgColor = 'linear-gradient(135deg, #FFC107 0%, #FFD54F 100%)'; // Amber
                    break;
                case 'class_survey':
                    bgColor = 'linear-gradient(135deg, #9C27B0 0%, #AB47BC 100%)'; // Purple
                    break;
            }
        }
        
        // Priority 2: Activity-based (Fallback if no filling_status_code)
        if (!bgColor) {
            if (act.includes('incoming')) statusClass = 'status-incoming';
            else if (act.includes('mainten') || act.includes('repair')) statusClass = 'status-maintenance';
            else if (act.includes('calib')) statusClass = 'status-calibration';
            else if (act.includes('ready')) statusClass = 'status-ready';
            else statusClass = 'status-filled';
            
            div.classList.add(statusClass);
        } else {
            // Apply direct color
            div.style.background = bgColor;
        }

        // Tooltip with filling status
        const statusDisplay = fillingStatusCode ? 
            fillingStatusCode.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 
            (tank.filling_status || 'Unknown');
        
        div.title = `No Isotank: ${tank.isotank_number}\nFilling Status: ${statusDisplay}\nActivity: ${div.dataset.activity}`;

        div.innerHTML = `<span class="tank-number">${tank.isotank_number}</span><span class="cargo">${tank.current_cargo || '-'}</span>`;
        if (ROLE_CAN_EDIT) {
            div.addEventListener('dragstart', handleDragStart);
            div.addEventListener('dragend', handleDragEnd);
        }
        return div;
    }

    function handleDragStart(e) { 
        draggedEl = e.target.closest('[data-isotank-id]');
        if (!draggedEl) return;
        
        e.dataTransfer.setData('text/plain', draggedEl.dataset.isotankId); 
        e.dataTransfer.effectAllowed = 'move'; 
        draggedEl.style.opacity = '0.5';
    }
    
    function handleDragEnd(e) {
        if (draggedEl) draggedEl.style.opacity = '1';
        draggedEl = null;
    }

    function handleDragOver(e) { 
        e.preventDefault(); 
        const slot = e.target.closest('.yard-slot'); 
        if (slot) { 
            slot.classList.add('drag-over'); 
            e.dataTransfer.dropEffect = 'move'; 
        } 
    }

    function handleDragLeave(e) { 
        const slot = e.target.closest('.yard-slot'); 
        if (slot) slot.classList.remove('drag-over'); 
    }

    async function handleDrop(e, targetSlotData) {
        e.preventDefault();
        const slotEl = e.target.closest('.yard-slot');
        if (slotEl) slotEl.classList.remove('drag-over');
        
        const isotankId = e.dataTransfer.getData('text/plain');
        if (!isotankId || !draggedEl) return;

        // Optimistic UI - Retrieve data from dataset
        const tankNumber = draggedEl.dataset.isotankNumber;
        const cargo = draggedEl.dataset.cargo;
        const fillingStatus = draggedEl.dataset.fillingStatus;
        const activity = draggedEl.dataset.activity;

        const cardToPlace = createTankCard({ 
            id: isotankId, 
            isotank_number: tankNumber, 
            current_cargo: cargo,
            filling_status: fillingStatus,
            activity: activity
        });
        
        slotEl.innerHTML = '';
        slotEl.removeAttribute('title'); // Remove area tooltip
        slotEl.appendChild(cardToPlace);
        
        const originalParent = draggedEl.parentElement;
        draggedEl.remove();
        
        // Restore label if emptying a slot
        if (originalParent && originalParent.classList.contains('yard-slot') && originalParent.children.length === 0) {
            // Re-render text based on stored title/structure would be best, 
            // but we can just simplify or let refresh handle perfect state.
            // For now, let's grab title as text fallback (Wait, we removed title? No, on empty slots in renderYard we set it)
            // But we can't easily recover "R13-C22" text without full re-render or storing it.
            // We can check if originalParent has dataset for area?
            // Actually, slotEl.title logic in renderYard is complex.
            // Let's just put "Empty" for now. Refresh is safer.
             originalParent.innerHTML = `<div style="text-align:center;line-height:1.2;">
                    <div style="font-size:10px; color:#94a3b8;">Empty</div>
                </div>`;
            // Restore title???
            // originalParent.title = "Empty"; 
        }

        try {
            const formData = new FormData();
            formData.append('isotank_id', isotankId);
            formData.append('slot_id', targetSlotData.id); // Changed from yard_cell_id
            
            const res = await fetch("{{ route('yard.move') }}", { 
                method: 'POST', 
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }, 
                body: formData 
            });
            const data = await res.json();
            
            if (res.ok) {
                loadYardData(true); 
            } else { 
                throw new Error(data.error || 'Move failed');
            }
        } catch (err) { 
            alert('Move Error: ' + err.message); 
            loadYardData();
        }
    }

</script>
@endpush
