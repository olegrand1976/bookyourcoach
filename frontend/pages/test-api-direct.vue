<template>
    <div class="p-8">
        <h1 class="text-2xl font-bold mb-4">🔧 Test Direct API</h1>

        <div class="bg-gray-100 p-4 rounded mb-4">
            <h2 class="text-lg font-semibold mb-2">Configuration</h2>
            <p><strong>API Base:</strong> {{ config.public.apiBase }}</p>
            <p><strong>Endpoint:</strong> /auth/login</p>
        </div>

        <div class="space-y-2 mb-4">
            <button @click="testDirectAPI" class="bg-blue-500 text-white px-4 py-2 rounded mr-2">Test API
                Direct</button>
            <button @click="testWithAxios" class="bg-green-500 text-white px-4 py-2 rounded mr-2">Test avec
                Axios</button>
            <button @click="testWithFetch" class="bg-purple-500 text-white px-4 py-2 rounded">Test avec Fetch</button>
        </div>

        <div class="bg-black text-green-400 p-4 rounded font-mono text-sm max-h-96 overflow-y-auto">
            <div v-for="log in logs" :key="log.id">{{ log.message }}</div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios'

const config = useRuntimeConfig()
const { $api } = useNuxtApp()

const logs = ref([])
let logId = 0

function addLog(message) {
    logs.value.push({
        id: logId++,
        message: `[${new Date().toLocaleTimeString()}] ${message}`
    })
}

async function testDirectAPI() {
    addLog('🔧 Test avec $api (plugin Nuxt)...')
    try {
        const response = await $api.post('/auth/login', {
            email: 'admin.secours@bookyourcoach.com',
            password: 'secours123'
        })
        addLog(`✅ Succès: ${response.data.user.role}`)
    } catch (error) {
        addLog(`❌ Erreur $api: ${error.message}`)
        if (error.response) {
            addLog(`   Status: ${error.response.status}`)
            addLog(`   Data: ${JSON.stringify(error.response.data)}`)
        }
    }
}

async function testWithAxios() {
    addLog('🔧 Test avec Axios direct...')
    try {
        const response = await axios.post(`${config.public.apiBase}/auth/login`, {
            email: 'admin.secours@bookyourcoach.com',
            password: 'secours123'
        }, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        addLog(`✅ Succès Axios: ${response.data.user.role}`)
    } catch (error) {
        addLog(`❌ Erreur Axios: ${error.message}`)
        if (error.response) {
            addLog(`   Status: ${error.response.status}`)
            addLog(`   Data: ${JSON.stringify(error.response.data)}`)
        }
    }
}

async function testWithFetch() {
    addLog('🔧 Test avec Fetch API...')
    try {
        const response = await fetch(`${config.public.apiBase}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                email: 'admin.secours@bookyourcoach.com',
                password: 'secours123'
            })
        })

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`)
        }

        const data = await response.json()
        addLog(`✅ Succès Fetch: ${data.user.role}`)
    } catch (error) {
        addLog(`❌ Erreur Fetch: ${error.message}`)
    }
}

onMounted(() => {
    addLog('🚀 Page de test API chargée')
    addLog(`🌐 API Base: ${config.public.apiBase}`)
})
</script>
