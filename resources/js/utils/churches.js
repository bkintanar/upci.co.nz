/**
 * Group churches from /api/churches under their organisational region.
 *
 * Unassigned sorts last; everything else keeps API order, which the churches
 * endpoint sorts by the regions table's own sort_order (north to south).
 *
 * @param {Array<object>} churches
 * @returns {Array<{ slug: string, name: string, churches: Array<object> }>}
 */
export function groupChurchesByRegion(churches) {
    const map = new Map()

    churches.forEach((church) => {
        const slug = church.organizational_region || 'unassigned'
        if (!map.has(slug)) {
            map.set(slug, {
                slug,
                name: church.organizational_region_name || 'Not yet assigned to a region',
                churches: [],
            })
        }
        map.get(slug).churches.push(church)
    })

    return [...map.values()].sort((a, b) =>
        (a.slug === 'unassigned' ? 1 : 0) - (b.slug === 'unassigned' ? 1 : 0)
    )
}
