import phpUnserialize from 'phpunserialize'

const utf8ByteLength = (value) => {
	if (typeof TextEncoder !== 'undefined') {
		return new TextEncoder().encode(value).length
	}

	// Fallback for very old environments
	return unescape(encodeURIComponent(value)).length
}

export const safePhpUnserialize = (data) => {
	const fixed = data.replace(
		/s:(\d+):"((?:\\.|[^"\\])*)";/g,
		(_, len, str) => {
			// If multiline, skip length correction to avoid breaking structure
			if (str.includes('\n')) return `s:${len}:"${str}";`
			const actualLength = utf8ByteLength(str)
			return `s:${actualLength}:"${str}";`
		}
	)

	return phpUnserialize(fixed)
}
