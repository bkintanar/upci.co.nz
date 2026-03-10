// Address Finder JavaScript - Updated: 2025-10-11 23:45:00
let mapInstance = null;
let mapContainerId = '';
let inputId = '';
let dropdownId = '';
let searchTimeout = null;
let leafletLoaded = false;
let userHasInteracted = false;

// Load Leaflet dynamically
function loadLeaflet() {
    return new Promise((resolve, reject) => {
        if (leafletLoaded && window.L) {
            resolve();
            return;
        }

        // Load CSS
        const cssLink = document.createElement('link');
        cssLink.rel = 'stylesheet';
        cssLink.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(cssLink);

        // Load JS
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => {
            leafletLoaded = true;
            resolve();
        };
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

function initializeMap() {
    const mapElement = document.getElementById(mapContainerId);

    if (mapElement) {
        // Check if container has placeholder content or needs initialization
        const hasPlaceholder = mapElement.querySelector('.text-center, .text-gray-500, .text-gray-400');
        const hasMap = mapElement.querySelector('.leaflet-container');

        if (hasPlaceholder || !hasMap) {
            console.log('Initializing map - placeholder content detected or no map found');

            loadLeaflet().then(() => {
                // Always clean up existing map first
                if (mapInstance) {
                    try {
                        mapInstance.remove();
                    } catch (e) {
                        console.log('Map already removed or invalid');
                    }
                    mapInstance = null;
                }

                // Clear the container completely
                mapElement.innerHTML = '';

                    // Check if there's an existing address to show
                    const existingAddress = getExistingAddress();

                    if (existingAddress) {
                        console.log('Found existing address:', existingAddress);

                        if (existingAddress.needsGeocoding) {
                            console.log('Address needs geocoding, attempting...');
                            geocodeAddress(existingAddress.label).then(coords => {
                                if (coords) {
                                    console.log('Geocoding successful:', coords);
                                    const geocodedAddress = {
                                        label: existingAddress.label,
                                        latitude: coords.lat,
                                        longitude: coords.lng
                                    };
                                    initializeMapWithAddress(geocodedAddress);
                                } else {
                                    console.log('Geocoding failed, using default Wellington');
                                    initializeMapWithDefault();
                                }
                            }).catch(error => {
                                console.log('Geocoding error:', error, 'using default Wellington');
                                initializeMapWithDefault();
                            });
                        } else {
                            initializeMapWithAddress(existingAddress);
                        }
                    } else {
                        console.log('No existing address found, using default Wellington');
                        initializeMapWithDefault();
                    }

                    function initializeMapWithAddress(address) {
                        console.log('Setting map view to:', address.latitude, address.longitude, 'zoom level 16');

                        // Check if map is already initialized
                        if (mapInstance) {
                            console.log('Map already exists, updating view and marker');

                            // Update map view
                            mapInstance.setView([address.latitude, address.longitude], 16);

                            // Remove existing marker if it exists
                            if (mapInstance.marker) {
                                mapInstance.removeLayer(mapInstance.marker);
                            }

                            // Add new marker
                            mapInstance.marker = L.marker([address.latitude, address.longitude])
                                .addTo(mapInstance)
                                .bindPopup(address.label)
                                .openPopup();

                            console.log('Map updated with existing address');
                        } else {
                            // Initialize new map with existing address at street level zoom
                            mapInstance = L.map(mapContainerId).setView([address.latitude, address.longitude], 16);

                            // Add CartoDB Positron tiles
                            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                                subdomains: 'abcd',
                                maxZoom: 20
                            }).addTo(mapInstance);

                            // Add marker for existing address
                            mapInstance.marker = L.marker([address.latitude, address.longitude])
                                .addTo(mapInstance)
                                .bindPopup(address.label)
                                .openPopup();

                            console.log('Map initialized with existing address');
                        }
                    }

                    function initializeMapWithDefault() {
                        // Check if map is already initialized
                        if (mapInstance) {
                            console.log('Map already exists, updating to default Wellington');
                            mapInstance.setView([-41.2924, 174.7787], 10);

                            // Remove existing marker if it exists
                            if (mapInstance.marker) {
                                mapInstance.removeLayer(mapInstance.marker);
                            }

                            // Add Wellington marker
                            mapInstance.marker = L.marker([-41.2924, 174.7787])
                                .addTo(mapInstance)
                                .bindPopup('Wellington, New Zealand')
                                .openPopup();

                            console.log('Map updated to default Wellington');
                        } else {
                            // Initialize map with default Wellington location
                            mapInstance = L.map(mapContainerId).setView([-41.2924, 174.7787], 10);

                            // Add CartoDB Positron tiles
                            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                                subdomains: 'abcd',
                                maxZoom: 20
                            }).addTo(mapInstance);

                            // Add a marker for Wellington
                            mapInstance.marker = L.marker([-41.2924, 174.7787])
                                .addTo(mapInstance)
                                .bindPopup('Wellington, New Zealand')
                                .openPopup();

                            console.log('Map initialized with default Wellington location');
                        }
                    }
            }).catch(error => {
                console.error('Failed to load Leaflet:', error);
            });
        } else {
            console.log('Map container already has map content, skipping initialization');
        }
    }
}

function getExistingAddress() {
    // Try to find existing address data from form fields
    const form = document.querySelector('form[wire\\:submit]') ||
                 document.querySelector('form[data-wire-submit]') ||
                 document.querySelector('form[action*="churches"]') ||
                 document.querySelector('form:not([action*="logout"])') ||
                 document.querySelector('form');

    if (!form) {
        console.log('No form found for existing address detection');
        return null;
    }

    // Get address from input field - try multiple approaches
    const addressInput = document.getElementById(inputId);
    let address = addressInput ? addressInput.value.trim() : '';

    // If no address from the address finder input, try to find other address fields
    if (!address) {
        const addressField = findField(form, [
            'input[name*="address"]',
            'input[name*="Address"]',
            'input[name*="street"]',
            'input[name*="Street"]',
            'input[name*="data.address"]',
            'input[name*="data[address]"]',
            'input[name$="address"]'
        ]);
        address = addressField ? addressField.value.trim() : '';
    }

    // If we still don't have an address, try to construct it from street + city + region + zip
    // But only if the user hasn't actively interacted with the input field
    if (!address && !userHasInteracted) {
        const streetField = findField(form, ['input[id*="street"]']);
        const cityField = findField(form, ['input[id*="city"]']);
        const regionField = findField(form, ['input[id*="region"]']);
        const zipField = findField(form, ['input[id*="zip"]']);

        if (streetField && cityField && regionField && zipField) {
            const street = streetField.value.trim();
            const city = cityField.value.trim();
            const region = regionField.value.trim();
            const zip = zipField.value.trim();

            if (street && city && region && zip) {
                address = `${street}, ${city}, ${region} ${zip}`;
                console.log('Constructed address from fields:', address);

                // Also populate the address field if it's empty
                if (addressInput && !addressInput.value.trim()) {
                    addressInput.value = address;
                    addressInput.dispatchEvent(new Event('input', { bubbles: true }));
                    console.log('Populated address field with constructed address');
                }
            }
        }
    }

    // Try to get coordinates from form fields
    // Filament generates field names like: data.latitude, data.longitude
    const latField = findField(form, [
        'input[name*="latitude"]',
        'input[name*="Latitude"]',
        'input[id*="latitude"]',
        'input[name*="data.latitude"]',
        'input[name*="data[latitude]"]',
        'input[name$="latitude"]'
    ]);
    const lngField = findField(form, [
        'input[name*="longitude"]',
        'input[name*="Longitude"]',
        'input[id*="longitude"]',
        'input[name*="data.longitude"]',
        'input[name*="data[longitude]"]',
        'input[name$="longitude"]'
    ]);

    const latitude = latField ? parseFloat(latField.value) : null;
    const longitude = lngField ? parseFloat(lngField.value) : null;

    // Debug: Log all input fields in the form
    const allInputs = form.querySelectorAll('input');
    console.log('All form inputs:', Array.from(allInputs).map(input => ({
        name: input.name,
        id: input.id,
        value: input.value,
        type: input.type
    })));

    console.log('Address detection:', {
        address: address,
        latitude: latitude,
        longitude: longitude,
        latField: latField,
        lngField: lngField
    });

    // If we have coordinates, use them
    if (latitude && longitude && !isNaN(latitude) && !isNaN(longitude) && latitude !== 0 && longitude !== 0) {
        const addressLabel = address || 'Stored Address';
        console.log('Using stored coordinates:', latitude, longitude);

        return {
            label: addressLabel,
            latitude: latitude,
            longitude: longitude
        };
    }

    // If we have an address but no coordinates, return a special marker for geocoding
    if (address && address.length > 5) {
        console.log('Found address but no valid coordinates, will geocode:', address);

        return {
            label: address,
            latitude: null,
            longitude: null,
            needsGeocoding: true
        };
    }

    console.log('No address or coordinates found, using default Wellington');
    return null;
}

function setupAddressSearch() {
    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);

    if (!input || !dropdown) return;

    // Remove any existing event listeners to prevent duplicates
    input.removeEventListener('input', handleInput);
    input.removeEventListener('keydown', handleKeydown);
    document.removeEventListener('click', handleClickOutside);

    // Add event listeners
    input.addEventListener('input', handleInput);
    input.addEventListener('keydown', handleKeydown);
    document.addEventListener('click', handleClickOutside);

        function handleInput(e) {
            const query = e.target.value.trim();

            // Mark that user has interacted with the input
            userHasInteracted = true;

            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            // Only hide dropdown if user is typing the exact same address as stored
            // This allows dropdown to show when user is changing the address
            const existingAddress = getExistingAddress();
            if (existingAddress && query === existingAddress.label && query.length > 10) {
                console.log('Input exactly matches stored address, keeping dropdown hidden');
                hideDropdown();
                return;
            }

            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    searchAddresses(query);
                }, 300);
            } else {
                hideDropdown();
            }
        }

    function handleKeydown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            // If dropdown is visible, select first result
            if (!dropdown.classList.contains('hidden') && dropdown.children.length > 0) {
                const firstResult = dropdown.children[0];
                firstResult.click();
            }
        } else if (e.key === 'Escape') {
            hideDropdown();
        }
    }

    function handleClickOutside(e) {
        // Check if click is outside both input and dropdown
        const container = document.querySelector('.address-finder-container');
        if (container && !container.contains(e.target)) {
            console.log('Click outside detected, hiding dropdown');
            hideDropdown();
        }
    }
}

async function searchAddresses(query) {
    try {
        const response = await fetch('/api/address-search', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ search: query })
        });

        if (response.ok) {
            const data = await response.json();
            displayResults(data.results || []);
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

function displayResults(results) {
    const dropdown = document.getElementById(dropdownId);

    // Only prevent dropdown if we have a complete existing address and user isn't actively searching
    const input = document.getElementById(inputId);
    const currentInput = input ? input.value.trim() : '';
    const existingAddress = getExistingAddress();

    if (existingAddress && existingAddress.label &&
        currentInput === existingAddress.label &&
        currentInput.length > 10) {
        console.log('Complete existing address detected, keeping dropdown hidden');
        hideDropdown();
        return;
    }

    if (results.length > 0) {
        dropdown.innerHTML = results.map(result => `
            <div class="address-finder-dropdown-item" onclick="selectAddress('${result.label}', ${result.latitude || -41.2924}, ${result.longitude || 174.7787})">
                <div class="address-finder-dropdown-item-main">${result.label}</div>
                <div class="address-finder-dropdown-item-sub">${result.city}, ${result.region}</div>
            </div>
        `).join('');
        showDropdown();
    } else {
        hideDropdown();
    }
}

function selectAddress(label, lat, lng) {
    console.log('selectAddress called with:', label); // Debug log

    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);

    if (!input || !dropdown) {
        console.error('Input or dropdown not found');
        return;
    }

    // Update input
    input.value = label;

    // Dispatch custom event for Livewire to handle
    const addressSelectedEvent = new CustomEvent('address-selected', {
        detail: {
            label: label,
            latitude: lat,
            longitude: lng
        }
    });
    document.dispatchEvent(addressSelectedEvent);

    // Hide dropdown immediately
    hideDropdown();
    console.log('Dropdown hidden after selection');

    // If coordinates are not provided or are default, geocode the address
    if (!lat || !lng || lat === 0 || lng === 0 || lat === -41.2924 || lng === 174.7787) {
        geocodeAddress(label).then(coords => {
            if (coords) {
                updateMapMarker({ label: label, latitude: coords.lat, longitude: coords.lng });
                updateFormFields(label, coords.lat, coords.lng);
            } else {
                // Fallback to default Wellington coordinates
                updateMapMarker({ label: label, latitude: -41.2924, longitude: 174.7787 });
                updateFormFields(label, -41.2924, 174.7787);
            }
        });
    } else {
        // Use provided coordinates
        updateMapMarker({ label: label, latitude: lat, longitude: lng });
        updateFormFields(label, lat, lng);
    }
}

function hideDropdown() {
    const dropdown = document.getElementById(dropdownId);
    console.log('hideDropdown called, dropdown element:', dropdown);
    console.log('dropdownId:', dropdownId);

    if (dropdown) {
        // Use multiple methods to ensure hiding
        dropdown.classList.add('hidden');
        dropdown.style.display = 'none';
        dropdown.style.visibility = 'hidden';
        dropdown.style.opacity = '0';
        dropdown.style.pointerEvents = 'none';
        console.log('Dropdown hidden via hideDropdown()');
        console.log('Dropdown classes after hiding:', dropdown.className);
    } else {
        console.error('Dropdown element not found for hiding');
    }
}

function showDropdown() {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        // Use multiple methods to ensure showing
        dropdown.classList.remove('hidden');
        dropdown.style.display = '';
        dropdown.style.visibility = '';
        dropdown.style.opacity = '';
        dropdown.style.pointerEvents = '';
        console.log('Dropdown shown via showDropdown()');
    } else {
        console.error('Dropdown element not found for showing');
    }
}

function updateFormFields(address, lat, lng) {
    console.log('updateFormFields called with:', address, lat, lng);

    // Find the correct form - look for the church form specifically
    let form = document.querySelector('form[wire\\:submit]') ||
               document.querySelector('form[data-wire-submit]') ||
               document.querySelector('form[action*="churches"]') ||
               document.querySelector('form:not([action*="logout"])') ||
               document.querySelector('form');

    console.log('Form found:', form);

    if (form) {
        // Try multiple selectors for each field type
        const fieldSelectors = {
            street: ['input[name*="street"]', 'input[name*="Street"]', 'input[name*="address"]', 'input[name*="Address"]', 'input[data-field-name*="street"]', 'input[id*="street"]'],
            suburb: ['input[name*="suburb"]', 'input[name*="Suburb"]', 'input[name*="data.suburb"]', 'input[name*="data[suburb]"]', 'input[data-field-name*="suburb"]', 'input[id*="suburb"]'],
            city: ['input[name*="city"]', 'input[name*="City"]', 'input[data-field-name*="city"]', 'input[id*="city"]'],
            region: ['input[name*="region"]', 'input[name*="Region"]', 'input[data-field-name*="region"]', 'input[id*="region"]'],
            zip: ['input[name*="zip"]', 'input[name*="Zip"]', 'input[name*="postcode"]', 'input[name*="Postcode"]', 'input[data-field-name*="zip"]', 'input[id*="zip"]'],
            latitude: ['input[name*="latitude"]', 'input[name*="Latitude"]', 'input[data-field-name*="latitude"]', 'input[id*="latitude"]'],
            longitude: ['input[name*="longitude"]', 'input[name*="Longitude"]', 'input[data-field-name*="longitude"]', 'input[id*="longitude"]'],
            country: ['input[name*="country"]', 'input[name*="Country"]', 'input[data-field-name*="country"]', 'input[id*="country"]']
        };

        // Update street field
        const streetField = findField(form, fieldSelectors.street);
        if (streetField) {
            const streetValue = extractStreetFromAddress(address);
            streetField.value = streetValue;
            streetField.dispatchEvent(new Event('input', { bubbles: true }));
            console.log('Updated street field:', streetValue);
        } else {
            console.log('Street field not found');
        }

        // Update city field
        const cityField = findField(form, fieldSelectors.city);
        if (cityField) {
            const cityValue = extractCityFromAddress(address);
            cityField.value = cityValue;
            cityField.dispatchEvent(new Event('input', { bubbles: true }));
            console.log('Updated city field:', cityValue);
        } else {
            console.log('City field not found');
        }

        // Update region field
        const regionField = findField(form, fieldSelectors.region);
        if (regionField) {
            const regionValue = extractRegionFromAddress(address);
            regionField.value = regionValue;
            regionField.dispatchEvent(new Event('input', { bubbles: true }));
            console.log('Updated region field:', regionValue);
        } else {
            console.log('Region field not found');
        }

        // Update suburb field
        const suburbField = findField(form, fieldSelectors.suburb);
        if (suburbField) {
            const suburbValue = extractSuburbFromAddress(address);
            suburbField.value = suburbValue;
            suburbField.dispatchEvent(new Event('input', { bubbles: true }));
            console.log('Updated suburb field:', suburbValue);
            console.log('Suburb field name:', suburbField.name, 'Suburb field id:', suburbField.id);
        } else {
            console.log('Suburb field not found');
            console.log('Available fields:', Array.from(form.querySelectorAll('input')).map(input => ({
                name: input.name,
                id: input.id,
                type: input.type
            })));
        }

        // Update zip field
        const zipField = findField(form, fieldSelectors.zip);
        if (zipField) {
            const zipValue = extractPostcodeFromAddress(address);
            zipField.value = zipValue;
            zipField.dispatchEvent(new Event('input', { bubbles: true }));
            console.log('Updated zip field:', zipValue);
        } else {
            console.log('Zip field not found');
        }

        // Update latitude field
        const latField = findField(form, fieldSelectors.latitude);
        if (latField) {
            latField.value = lat;
            latField.dispatchEvent(new Event('input', { bubbles: true }));
            console.log('Updated latitude field:', lat);
        } else {
            console.log('Latitude field not found');
        }

        // Update longitude field
        const lngField = findField(form, fieldSelectors.longitude);
        if (lngField) {
            lngField.value = lng;
            lngField.dispatchEvent(new Event('input', { bubbles: true }));
            console.log('Updated longitude field:', lng);
        } else {
            console.log('Longitude field not found');
        }

        // Update country field
        const countryField = findField(form, fieldSelectors.country);
        if (countryField) {
            countryField.value = 'New Zealand';
            countryField.dispatchEvent(new Event('input', { bubbles: true }));
            console.log('Updated country field: New Zealand');
        } else {
            console.log('Country field not found');
        }
    } else {
        console.error('Form not found');
    }
}

function findField(form, selectors) {
    for (const selector of selectors) {
        const field = form.querySelector(selector);
        if (field) {
            console.log('Found field with selector:', selector);
            return field;
        }
    }
    return null;
}

function extractCityFromAddress(address) {
    const parts = address.split(', ');
    if (parts.length >= 2) {
        // Get the last part (city + postcode) and extract city
        const lastPart = parts[parts.length - 1].trim();
        // Remove postcode (4 digits at the end)
        const city = lastPart.replace(/\s+\d{4}$/, '').trim();

        // Handle common city variations
        if (city.toLowerCase().includes('auckland')) return 'Auckland';
        if (city.toLowerCase().includes('wellington')) return 'Wellington';
        if (city.toLowerCase().includes('christchurch')) return 'Christchurch';
        if (city.toLowerCase().includes('hamilton')) return 'Hamilton';
        if (city.toLowerCase().includes('dunedin')) return 'Dunedin';
        if (city.toLowerCase().includes('tauranga')) return 'Tauranga';
        if (city.toLowerCase().includes('napier')) return 'Napier';
        if (city.toLowerCase().includes('hastings')) return 'Hastings';
        if (city.toLowerCase().includes('palmerston north')) return 'Palmerston North';
        if (city.toLowerCase().includes('whanganui')) return 'Whanganui';
        if (city.toLowerCase().includes('invercargill')) return 'Invercargill';

        return city;
    }
    return '';
}

function extractStreetFromAddress(address) {
    const parts = address.split(', ');
    if (parts.length >= 1) {
        return parts[0].trim();
    }
    return '';
}

function extractSuburbFromAddress(address) {
    const parts = address.split(', ');
    if (parts.length >= 2) {
        return parts[1].trim();
    }
    return '';
}

function extractRegionFromAddress(address) {
    const parts = address.split(', ');
    if (parts.length >= 2) {
        // Get the last part (city + postcode) and extract city
        const lastPart = parts[parts.length - 1].trim();
        const city = lastPart.replace(/\s+\d{4}$/, '').trim();

        // Map cities to regions based on NZ regions
        const cityLower = city.toLowerCase();

        // Auckland Region
        if (cityLower.includes('auckland')) return 'Auckland';

        // Wellington Region
        if (cityLower.includes('wellington')) return 'Wellington';

        // Canterbury Region
        if (cityLower.includes('christchurch')) return 'Canterbury';

        // Waikato Region
        if (cityLower.includes('hamilton')) return 'Waikato';

        // Otago Region
        if (cityLower.includes('dunedin')) return 'Otago';

        // Bay of Plenty Region
        if (cityLower.includes('tauranga')) return 'Bay of Plenty';

        // Hawke's Bay Region
        if (cityLower.includes('napier') || cityLower.includes('hastings')) return 'Hawke\'s Bay';

        // Manawatu-Wanganui Region
        if (cityLower.includes('palmerston north') || cityLower.includes('whanganui')) return 'Manawatu-Wanganui';

        // Southland Region
        if (cityLower.includes('invercargill')) return 'Southland';

        // Nelson Region
        if (cityLower.includes('nelson')) return 'Nelson';

        // Marlborough Region
        if (cityLower.includes('blenheim')) return 'Marlborough';

        // Tasman Region
        if (cityLower.includes('richmond') && cityLower.includes('tasman')) return 'Tasman';

        // West Coast Region
        if (cityLower.includes('greymouth') || cityLower.includes('westport')) return 'West Coast';

        // Gisborne Region
        if (cityLower.includes('gisborne')) return 'Gisborne';

        // If no specific mapping, return the city name
        return city;
    }
    return '';
}

function extractPostcodeFromAddress(address) {
    const match = address.match(/(\d{4})$/);
    return match ? match[1] : '';
}

async function geocodeAddress(address) {
    try {
        // Use OpenStreetMap Nominatim API for geocoding
        const encodedAddress = encodeURIComponent(address + ', New Zealand');
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodedAddress}&limit=1&countrycodes=nz`);

        if (response.ok) {
            const data = await response.json();
            if (data && data.length > 0) {
                return {
                    lat: parseFloat(data[0].lat),
                    lng: parseFloat(data[0].lon)
                };
            }
        }
    } catch (error) {
        console.error('Geocoding error:', error);
    }

    return null;
}

function updateMapMarker(address) {
    if (address) {
        loadLeaflet().then(() => {
            if (mapInstance) {
                // Use coordinates if available, otherwise use default Wellington
                const lat = address.latitude || -41.2924;
                const lng = address.longitude || 174.7787;

                // Clear existing markers
                mapInstance.eachLayer(function(layer) {
                    if (layer instanceof L.Marker) {
                        mapInstance.removeLayer(layer);
                    }
                });

                // Add new marker
                L.marker([lat, lng])
                    .addTo(mapInstance)
                    .bindPopup(address.label || 'Selected Address')
                    .openPopup();

                // Pan to the new location
                mapInstance.setView([lat, lng], 15);

                console.log('Map marker updated to:', address.label);
            }
        }).catch(error => {
            console.error('Failed to load Leaflet for marker update:', error);
        });
    }
}

// Initialize address finder for a specific component
function initializeAddressFinder(componentId) {
    mapContainerId = 'address-map-' + componentId;
    inputId = 'address-input-' + componentId;
    dropdownId = 'address-dropdown-' + componentId;

    // Initialize map with delay to ensure DOM is ready
    setTimeout(() => {
        initializeMap();
        setupAddressSearch();
    }, 100);
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Find all address finder components and initialize them
    const addressInputs = document.querySelectorAll('[id^="address-input-"]');
    addressInputs.forEach(input => {
        const id = input.id.replace('address-input-', '');
        initializeAddressFinder(id);
    });
});

// Re-initialize after Livewire updates
document.addEventListener('livewire:navigated', function() {
    setTimeout(() => {
        const addressInputs = document.querySelectorAll('[id^="address-input-"]');
        addressInputs.forEach(input => {
            const id = input.id.replace('address-input-', '');
            initializeAddressFinder(id);
        });
    }, 100);
});

// Fallback: Check for map persistence every 2 seconds
setInterval(() => {
    const addressInputs = document.querySelectorAll('[id^="address-input-"]');
    addressInputs.forEach(input => {
        const id = input.id.replace('address-input-', '');
        const mapElement = document.getElementById('address-map-' + id);

        if (mapElement && !mapElement.hasChildNodes()) {
            console.log('Map container empty, re-initializing...');
            initializeAddressFinder(id);
        }
    });
}, 2000);
