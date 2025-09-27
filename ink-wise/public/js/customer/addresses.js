document.addEventListener('DOMContentLoaded', function() {
    // Show/Hide Add Address Modal
    const addBtn = document.getElementById('addAddressBtn');
    const addModal = document.getElementById('addAddressModal');
    const closeAdd1 = document.getElementById('closeAddAddress');
    const closeAdd2 = document.getElementById('closeAddAddress2');
    if (addBtn && addModal) {
        addBtn.addEventListener('click', () => addModal.classList.remove('hidden'));
    }
    if (closeAdd1 && addModal) {
        closeAdd1.addEventListener('click', () => addModal.classList.add('hidden'));
    }
    if (closeAdd2 && addModal) {
        closeAdd2.addEventListener('click', () => addModal.classList.add('hidden'));
    }

    // Show/Hide Edit Address Modal
    const editModal = document.getElementById('editAddressModal');
    const closeEdit1 = document.getElementById('closeEditAddress');
    const closeEdit2 = document.getElementById('closeEditAddress2');
    if (closeEdit1 && editModal) {
        closeEdit1.addEventListener('click', () => editModal.classList.add('hidden'));
    }
    if (closeEdit2 && editModal) {
        closeEdit2.addEventListener('click', () => editModal.classList.add('hidden'));
    }

    // Edit Address Button
    document.querySelectorAll('.edit-address-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const address = JSON.parse(this.dataset.address);
            document.getElementById('edit_full_name').value = address.full_name || '';
            document.getElementById('edit_phone').value = address.phone || '';
            document.getElementById('edit_region').value = address.region || '';
            document.getElementById('edit_region').dispatchEvent(new Event('change'));
            setTimeout(() => {
                document.getElementById('edit_province').value = address.province || '';
                document.getElementById('edit_province').dispatchEvent(new Event('change'));
                setTimeout(() => {
                    document.getElementById('edit_city').value = address.city || '';
                }, 100);
            }, 100);
            document.getElementById('edit_barangay').value = address.barangay || '';
            document.getElementById('edit_postal_code').value = address.postal_code || '';
            document.getElementById('edit_street').value = address.street || '';
            document.getElementById('edit_label').value = address.label || 'Home';
            document.getElementById('editAddressForm').action = `/customerprofile/addresses/${address.address_id}/update`;
            editModal.classList.remove('hidden');
        });
    });

    // Update Google Maps iframe as user selects/inputs address fields
    function updateMap() {
        const region = document.getElementById('region')?.value || '';
        const province = document.getElementById('province')?.value || '';
        const city = document.getElementById('city')?.value || '';
        const barangay = document.getElementById('barangay')?.value || '';
        const street = document.getElementById('street')?.value || '';
        const postal = document.getElementById('postal_code')?.value || '';
        let address = [street, barangay, city, province, region, postal].filter(Boolean).join(', ');
        if (!address) address = 'Cebu City';
        document.getElementById('map_embed').src =
            "https://maps.google.com/maps?q=" + encodeURIComponent(address) + "&t=&z=15&ie=UTF8&iwloc=&output=embed";
    }

    ['region', 'province', 'city', 'barangay', 'street', 'postal_code'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', updateMap);
        document.getElementById(id)?.addEventListener('change', updateMap);
    });

    // Philippine locations data
    const phLocations = {
        "Ilocos Region (Region I)": {
            "Ilocos Norte": ["Laoag City", "Batac City"],
            "Ilocos Sur": ["Vigan City", "Candon City"],
            "La Union": ["San Fernando City"],
            "Pangasinan": ["Dagupan City", "San Carlos City"]
        },
        "Cagayan Valley (Region II)": {
            "Batanes": ["Basco"],
            "Cagayan": ["Tuguegarao City"],
            "Isabela": ["Ilagan City", "Cauayan City"],
            "Nueva Vizcaya": ["Bayombong"],
            "Quirino": ["Cabarroguis"]
        },
        "Central Luzon (Region III)": {
            "Aurora": ["Baler"],
            "Bataan": ["Balanga City"],
            "Bulacan": ["Malolos City"],
            "Nueva Ecija": ["Cabanatuan City", "Palayan City"],
            "Pampanga": ["San Fernando City"],
            "Tarlac": ["Tarlac City"],
            "Zambales": ["Olongapo City"]
        },
        "CALABARZON (Region IV-A)": {
            "Batangas": ["Batangas City", "Lipa City"],
            "Cavite": ["Tagaytay City", "Trece Martires City"],
            "Laguna": ["San Pablo City", "Santa Rosa City"],
            "Quezon": ["Lucena City"],
            "Rizal": ["Antipolo City"]
        },
        "MIMAROPA (Region IV-B)": {
            
            "Marinduque": ["Boac"],
            "Occidental Mindoro": ["Mamburao"],
            "Oriental Mindoro": ["Calapan City"],
            "Palawan": ["Puerto Princesa City"],
            "Romblon": ["Romblon"]
        },
        "Bicol Region (Region V)": {
            "Albay": ["Legazpi City", "Tabaco City"],
            "Camarines Norte": ["Daet"],
            "Camarines Sur": ["Naga City", "Iriga City"],
            "Catanduanes": ["Virac"],
            "Masbate": ["Masbate City"],
            "Sorsogon": ["Sorsogon City"]
        },
        "Western Visayas (Region VI)": {
            "Aklan": ["Kalibo"],
            "Antique": ["San Jose"],
            "Capiz": ["Roxas City"],
            "Guimaras": ["Jordan"],
            "Iloilo": ["Iloilo City", "Passi City"],
            "Negros Occidental": ["Bacolod City"]
        },
        "Central Visayas (Region VII)": {
            "Bohol": ["Tagbilaran City"],
            "Cebu": ["Cebu City", "Mandaue City", "Lapu-Lapu City"],
            "Negros Oriental": ["Dumaguete City"],
            "Siquijor": ["Siquijor"]
        },
        "Eastern Visayas (Region VIII)": {
            "Biliran": ["Naval"],
            "Eastern Samar": ["Borongan City"],
            "Leyte": ["Tacloban City", "Ormoc City"],
            "Northern Samar": ["Catarman"],
            "Samar": ["Catbalogan City"],
            "Southern Leyte": ["Maasin City"]
        },
        "Zamboanga Peninsula (Region IX)": {
            "Zamboanga del Norte": ["Dipolog City", "Dapitan City"],
            "Zamboanga del Sur": ["Pagadian City", "Zamboanga City"],
            "Zamboanga Sibugay": ["Ipil"]
        },
        "Northern Mindanao (Region X)": {
            "Bukidnon": ["Malaybalay City", "Valencia City"],
            "Camiguin": ["Mambajao"],
            "Lanao del Norte": ["Iligan City"],
            "Misamis Occidental": ["Oroquieta City", "Ozamiz City"],
            "Misamis Oriental": ["Cagayan de Oro City", "Gingoog City"]
        },
        "Davao Region (Region XI)": {
            "Davao de Oro": ["Nabunturan"],
            "Davao del Norte": ["Tagum City", "Panabo City"],
            "Davao del Sur": ["Davao City", "Digos City"],
            "Davao Occidental": ["Malita"],
            "Davao Oriental": ["Mati City"]
        },
        "SOCCSKSARGEN (Region XII)": {
            "Cotabato": ["Kidapawan City"],
            "Sarangani": ["Alabel"],
            "South Cotabato": ["Koronadal City", "General Santos City"],
            "Sultan Kudarat": ["Isulan"],
            "Cotabato City": ["Cotabato City"]
        },
        "Caraga (Region XIII)": {
            "Agusan del Norte": ["Butuan City", "Cabadbaran City"],
            "Agusan del Sur": ["Prosperidad"],
            "Dinagat Islands": ["San Jose"],
            "Surigao del Norte": ["Surigao City"],
            "Surigao del Sur": ["Tandag City", "Bislig City"]
        },
        "Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)": {
            "Basilan": ["Isabela City"],
            "Lanao del Sur": ["Marawi City"],
            "Maguindanao": ["Buluan"],
            "Sulu": ["Jolo"],
            "Tawi-Tawi": ["Bongao"]
        },
        "Cordillera Administrative Region (CAR)": {
            "Abra": ["Bangued"],
            "Apayao": ["Kabugao"],
            "Benguet": ["La Trinidad", "Baguio City"],
            "Ifugao": ["Lagawe"],
            "Kalinga": ["Tabuk City"],
            "Mountain Province": ["Bontoc"]
        },
        "National Capital Region (NCR)": {
            "Metro Manila": [
                "Caloocan City", "Las Piñas City", "Makati City", "Malabon City", "Mandaluyong City",
                "Manila City", "Marikina City", "Muntinlupa City", "Navotas City", "Parañaque City",
                "Pasay City", "Pasig City", "Quezon City", "San Juan City", "Taguig City", "Valenzuela City"
            ]
        }
    };

    document.getElementById('region').addEventListener('change', function() {
        const region = this.value;
        const provinceSelect = document.getElementById('province');
        const citySelect = document.getElementById('city');
        provinceSelect.innerHTML = '<option value="">Select Province</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';
        if (phLocations[region]) {
            Object.keys(phLocations[region]).forEach(province => {
                provinceSelect.innerHTML += `<option value="${province}">${province}</option>`;
            });
        }
    });

    document.getElementById('province').addEventListener('change', function() {
        const region = document.getElementById('region').value;
        const province = this.value;
        const citySelect = document.getElementById('city');
        citySelect.innerHTML = '<option value="">Select City</option>';
        if (phLocations[region] && phLocations[region][province]) {
            phLocations[region][province].forEach(city => {
                citySelect.innerHTML += `<option value="${city}">${city}</option>`;
            });
        }
    });

    // Populate edit form and open modal
    document.querySelectorAll('.edit-address-btn').forEach(button => {
        button.addEventListener('click', function() {
            const address = JSON.parse(this.getAttribute('data-address'));
            document.getElementById('edit_full_name').value = address.full_name;
            document.getElementById('edit_phone').value = address.phone;
            document.getElementById('edit_region').value = address.region;
            document.getElementById('edit_province').value = address.province;
            document.getElementById('edit_city').value = address.city;
            document.getElementById('edit_barangay').value = address.barangay;
            document.getElementById('edit_postal_code').value = address.postal_code;
            document.getElementById('edit_street').value = address.street;
            document.getElementById('edit_label').value = address.label;

            // Update cities based on province
            const province = address.province;
            const citySelect = document.getElementById('edit_city');
            citySelect.innerHTML = '<option value="">Select City</option>';
            if (phLocations[address.region] && phLocations[address.region][province]) {
                phLocations[address.region][province].forEach(city => {
                    citySelect.innerHTML += `<option value="${city}">${city}</option>`;
                });
            }

            document.getElementById('editAddressModal').classList.remove('hidden');
        });
    });

    // Update Google Maps iframe for edit form
    function updateEditMap() {
        const region = document.getElementById('edit_region')?.value || '';
        const province = document.getElementById('edit_province')?.value || '';
        const city = document.getElementById('edit_city')?.value || '';
        const barangay = document.getElementById('edit_barangay')?.value || '';
        const street = document.getElementById('edit_street')?.value || '';
        const postal = document.getElementById('edit_postal_code')?.value || '';
        let address = [street, barangay, city, province, region, postal].filter(Boolean).join(', ');
        if (!address) address = 'Cebu City';
        document.getElementById('map_embed').src =
            "https://maps.google.com/maps?q=" + encodeURIComponent(address) + "&t=&z=15&ie=UTF8&iwloc=&output=embed";
    }

    ['edit_region', 'edit_province', 'edit_city', 'edit_barangay', 'edit_street', 'edit_postal_code'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', updateEditMap);
        document.getElementById(id)?.addEventListener('change', updateEditMap);
    });
});
