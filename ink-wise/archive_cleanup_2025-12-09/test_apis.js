// Test script to check icon API endpoints with the provided key
const API_KEY = '8927515d78814c93b614be0aa46b1173';
const TEST_QUERY = 'heart';
const PER_PAGE = 10;

async function testEndpoint(endpoint, headers = {}) {
  try {
    console.log(`\nTesting: ${endpoint.substring(0, 60)}...`);
    const response = await fetch(endpoint, { headers });
    console.log(`Status: ${response.status}`);

    if (response.ok) {
      const data = await response.json();
      console.log('✅ SUCCESS!');
      console.log('Response keys:', Object.keys(data));
      if (data.data) console.log('Has data array:', data.data.length, 'items');
      if (data.icons) console.log('Has icons array:', data.icons.length, 'items');
      if (data.result) console.log('Has result array:', data.result.length, 'items');
      return { success: true, data };
    } else {
      const errorText = await response.text();
      console.log('❌ FAILED:', errorText.substring(0, 100));
      return { success: false, error: errorText };
    }
  } catch (error) {
    console.log('❌ NETWORK ERROR:', error.message);
    return { success: false, error: error.message };
  }
}

async function testAllEndpoints() {
  console.log('Testing icon API endpoints with key:', API_KEY.substring(0, 10) + '...');

  const endpoints = [
    // Free APIs that don't require authentication
    {
      name: 'Lordicon (Free)',
      url: `https://api.lordicon.com/icons/search?q=${encodeURIComponent(TEST_QUERY)}&limit=${PER_PAGE}`,
      headers: {}
    },
    {
      name: 'React Icons (CDN)',
      url: `https://api.github.com/search/code?q=${encodeURIComponent(TEST_QUERY)}+repo:react-icons/react-icons&per_page=${PER_PAGE}`,
      headers: {}
    },
    {
      name: 'Simple Icons',
      url: `https://raw.githubusercontent.com/simple-icons/simple-icons/develop/_data/simple-icons.json`,
      headers: {}
    },
    // APIs that might work with the provided key
    {
      name: 'Flaticon v3',
      url: `https://api.flaticon.com/v3/search/icons?q=${encodeURIComponent(TEST_QUERY)}&apikey=${API_KEY}&page=1&limit=${PER_PAGE}`,
      headers: {}
    },
    {
      name: 'IconFinder',
      url: `https://api.iconfinder.com/v4/icons/search?query=${encodeURIComponent(TEST_QUERY)}&count=${PER_PAGE}&offset=0`,
      headers: { 'Authorization': `Bearer ${API_KEY}` }
    }
  ];

  const results = {};

  for (const endpoint of endpoints) {
    results[endpoint.name] = await testEndpoint(endpoint.url, endpoint.headers);
  }

  console.log('\n=== SUMMARY ===');
  Object.entries(results).forEach(([name, result]) => {
    console.log(`${name}: ${result.success ? '✅ WORKS' : '❌ FAILS'}`);
  });

  const workingServices = Object.entries(results)
    .filter(([_, result]) => result.success)
    .map(([name]) => name);

  if (workingServices.length > 0) {
    console.log('\n🎉 Working services:', workingServices.join(', '));
  } else {
    console.log('\n😞 No services are working with this API key');
  }
}

// Run the test
testAllEndpoints().catch(console.error);
