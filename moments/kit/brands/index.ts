// distinctUI's copy of the Stageshow kit ships without the brand registry:
// distinctUI moments never show other companies' logos.
export type BrandAsset = {
  light: string
  dark: string
  tileArtwork?: string
  tileBackground?: string
  name?: string
  category?: string
  proportion?: number
  scale?: number
}

export const brandMarks: Record<string, BrandAsset> = {}

export type BrandName = string
