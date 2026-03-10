    <style>
        .address-finder-container {
            position: relative;
        }

        .address-finder-dropdown {
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 9999 !important;
            margin-top: 8px !important;
            width: 100% !important;
            background: white !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 8px !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            max-height: 280px !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            backdrop-filter: blur(8px) !important;
        }

        .address-finder-dropdown.hidden {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        .dark .address-finder-dropdown {
            background: rgba(31, 41, 55, 0.95) !important;
            border-color: #4b5563 !important;
            backdrop-filter: blur(12px) !important;
        }

        .address-finder-dropdown-item {
            padding: 16px 20px !important;
            cursor: pointer !important;
            border-bottom: 1px solid #f3f4f6 !important;
            transition: all 0.2s ease-in-out !important;
            position: relative !important;
            word-wrap: break-word !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
        }

        .address-finder-dropdown-item:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
            transform: translateX(2px) !important;
        }

        .dark .address-finder-dropdown-item:hover {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%) !important;
        }

        .address-finder-dropdown-item:last-child {
            border-bottom: none !important;
            border-radius: 0 0 8px 8px !important;
        }

        .address-finder-dropdown-item:first-child {
            border-radius: 8px 8px 0 0 !important;
        }

        .address-finder-dropdown-item-main {
            font-weight: 600 !important;
            color: #111827 !important;
            font-size: 15px !important;
            line-height: 1.4 !important;
            margin-bottom: 4px !important;
            word-wrap: break-word !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
        }

        .dark .address-finder-dropdown-item-main {
            color: #f9fafb !important;
        }

        .address-finder-dropdown-item-sub {
            font-size: 13px !important;
            color: #6b7280 !important;
            font-weight: 500 !important;
            display: flex !important;
            align-items: center !important;
            word-wrap: break-word !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
        }

        .dark .address-finder-dropdown-item-sub {
            color: #d1d5db !important;
        }

        .address-finder-dropdown-item-sub::before {
            content: "📍" !important;
            margin-right: 6px !important;
            font-size: 12px !important;
        }

        /* Custom scrollbar */
        .address-finder-dropdown::-webkit-scrollbar {
            width: 6px !important;
        }

        .address-finder-dropdown::-webkit-scrollbar-track {
            background: #f1f5f9 !important;
            border-radius: 3px !important;
        }

        .address-finder-dropdown::-webkit-scrollbar-thumb {
            background: #cbd5e1 !important;
            border-radius: 3px !important;
        }

        .address-finder-dropdown::-webkit-scrollbar-thumb:hover {
            background: #94a3b8 !important;
        }

        .dark .address-finder-dropdown::-webkit-scrollbar-track {
            background: #374151 !important;
        }

        .dark .address-finder-dropdown::-webkit-scrollbar-thumb {
            background: #6b7280 !important;
        }

        .dark .address-finder-dropdown::-webkit-scrollbar-thumb:hover {
            background: #9ca3af !important;
        }
    </style>

    <div class="space-y-4">
        <!-- Address Input with Dropdown -->
        <div class="address-finder-container">
            <div class="fi-input-wrp">
                <div class="fi-input-container">
                    <input
                        type="text"
                        id="address-input-{{ $getViewData()['id'] }}"
                        placeholder="Start typing to search for New Zealand addresses..."
                        class="fi-input"
                    />
                </div>
            </div>

            <!-- Dropdown Results -->
            <div
                id="address-dropdown-{{ $getViewData()['id'] }}"
                class="address-finder-dropdown hidden"
            >
                <!-- Results will be populated here -->
            </div>
        </div>

        <!-- Map Container -->
        <div class="mt-4" style="margin-top: 21px;">
            <div id="address-map-{{ $getViewData()['id'] }}" class="w-full h-64 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-800" style="min-height: 256px;"></div>
        </div>
    </div>

    <script>
        // Listen for address selection events
        document.addEventListener('address-selected', function(event) {
            console.log('Address selected event received:', event.detail);

            // Find the Livewire component and trigger the callback
            const form = document.querySelector('form[wire\\:submit]') ||
                         document.querySelector('form[data-wire-submit]') ||
                         document.querySelector('form[action*="churches"]') ||
                         document.querySelector('form:not([action*="logout"])') ||
                         document.querySelector('form');

            if (form && form.getAttribute('wire:id')) {
                const wireId = form.getAttribute('wire:id');
                const livewireComponent = window.Livewire.find(wireId);

                if (livewireComponent) {
                    // Trigger the handleAddressSelected method
                    livewireComponent.call('handleAddressSelected', event.detail);
                    console.log('Successfully triggered handleAddressSelected method');
                }
            }
        });
    </script>
