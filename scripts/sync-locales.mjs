#!/usr/bin/env node
import fs from 'fs'
import path from 'path'

const localesDir = path.resolve(process.cwd(), 'frontend/locales')
const referenceLocale = 'fr.json'

function readJson(filePath) {
	return JSON.parse(fs.readFileSync(filePath, 'utf8'))
}

function writeJson(filePath, data) {
	fs.writeFileSync(filePath, JSON.stringify(data, null, 4) + '\n', 'utf8')
}

function mergeMissingKeys(reference, target) {
	if (Array.isArray(reference) || Array.isArray(target)) {
		return target ?? reference
	}
	if (typeof reference !== 'object' || reference === null) {
		return target ?? reference
	}
	const result = { ...(target || {}) }
	for (const key of Object.keys(reference)) {
		if (result[key] === undefined) {
			result[key] = reference[key]
		} else {
			result[key] = mergeMissingKeys(reference[key], result[key])
		}
	}
	return result
}

function countMissingKeys(reference, target, prefix = '') {
	let count = 0
	if (typeof reference !== 'object' || reference === null) return 0
	for (const key of Object.keys(reference)) {
		const fullKey = prefix ? `${prefix}.${key}` : key
		if (target?.[key] === undefined) {
			count++
		} else if (typeof reference[key] === 'object' && reference[key] !== null) {
			count += countMissingKeys(reference[key], target[key], fullKey)
		}
	}
	return count
}

function main() {
	const refPath = path.join(localesDir, referenceLocale)
	if (!fs.existsSync(refPath)) {
		console.error(`Reference locale not found: ${refPath}`)
		process.exit(1)
	}
	const ref = readJson(refPath)
	const files = fs.readdirSync(localesDir).filter(f => f.endsWith('.json') && f !== referenceLocale)
	let totalFilled = 0
	for (const file of files) {
		const filePath = path.join(localesDir, file)
		const current = readJson(filePath)
		const missingBefore = countMissingKeys(ref, current)
		const merged = mergeMissingKeys(ref, current)
		writeJson(filePath, merged)
		const missingAfter = countMissingKeys(ref, merged)
		const filled = missingBefore - missingAfter
		totalFilled += filled
		console.log(`Updated ${file}: filled ${filled}, remaining ${missingAfter}`)
	}
	console.log(`Done. Total keys filled: ${totalFilled}`)
}

main()