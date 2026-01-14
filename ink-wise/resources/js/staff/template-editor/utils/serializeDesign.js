export function serializeDesign(state) {
  const pages = Array.isArray(state.pages)
    ? state.pages.map((page) => ({
        id: page.id,
        name: page.name,
        pageType: page.pageType ?? null,
        width: page.width,
        height: page.height,
        background: page.background,
        safeZone: page.safeZone ?? null,
        bleed: page.bleed ?? null,
        metadata: page.metadata ?? {},
        nodes: Array.isArray(page.nodes)
          ? page.nodes.map((node) => ({
              id: node.id,
              name: node.name,
              type: node.type,
              visible: node.visible !== false,
              locked: !!node.locked,
              editable: node.editable !== undefined ? Boolean(node.editable) : true,
              opacity: typeof node.opacity === 'number' ? node.opacity : 1,
              frame: node.frame ? { ...node.frame } : null,
              fill: node.fill ?? null,
              stroke: node.stroke ?? null,
              content: node.content ?? '',
              fontSize: node.fontSize ?? null,
              fontFamily: node.fontFamily ?? null,
              fontWeight: node.fontWeight ?? null,
              textAlign: node.textAlign ?? null,
              borderRadius: node.borderRadius ?? 0,
              variant: node.variant ?? null,
              metadata: node.metadata ?? {},
              replaceable: node.replaceable ?? false,
            }))
          : [],
      }))
    : [];

  return {
    pages,
    activePageId: state.activePageId ?? null,
    selectedLayerId: state.selectedLayerId ?? null,
    canvas: {
      zoom: state.zoom ?? 1,
      panX: state.panX ?? 0,
      panY: state.panY ?? 0,
    },
  };
}
