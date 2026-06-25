interface LogoProps {
  size?: 'sm' | 'md' | 'lg'
}

const SIZE = { sm: 22, md: 28, lg: 36 }

export default function Logo({ size = 'md' }: LogoProps) {
  const h = SIZE[size]
  const w = Math.round(h * 1.4)

  return (
    <span className={`logo logo--${size}`} aria-label="Pontis">
      <BridgeIcon width={w} height={h} />
      <span className="logo-text">Pontis</span>
    </span>
  )
}

function BridgeIcon({ width, height }: { width: number; height: number }) {
  return (
    <svg
      width={width}
      height={height}
      viewBox="0 0 28 20"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      aria-hidden="true"
    >
      {/* Anchor cables */}
      <line x1="6.5"  y1="2" x2="0.5" y2="14" stroke="var(--accent)" strokeWidth="1.2" strokeLinecap="round" opacity="0.55"/>
      <line x1="21.5" y1="2" x2="27.5" y2="14" stroke="var(--accent)" strokeWidth="1.2" strokeLinecap="round" opacity="0.55"/>

      {/* Main span cable */}
      <path d="M6.5 2 Q14 11 21.5 2" stroke="var(--accent)" strokeWidth="1.8" strokeLinecap="round"/>

      {/* Suspenders */}
      <line x1="10.5" y1="5.2" x2="10.5" y2="14" stroke="var(--accent)" strokeWidth="0.9" opacity="0.7"/>
      <line x1="14"   y1="6.2" x2="14"   y2="14" stroke="var(--accent)" strokeWidth="0.9" opacity="0.7"/>
      <line x1="17.5" y1="5.2" x2="17.5" y2="14" stroke="var(--accent)" strokeWidth="0.9" opacity="0.7"/>

      {/* Towers */}
      <rect x="5"  y="1" width="3" height="13" rx="0.8" fill="var(--accent)"/>
      <rect x="20" y="1" width="3" height="13" rx="0.8" fill="var(--accent)"/>

      {/* Deck */}
      <rect x="0" y="13.5" width="28" height="3" rx="1.2" fill="var(--accent)"/>

      {/* Water */}
      <path d="M2 18 Q5 17.2 8 18"   stroke="var(--accent)" strokeWidth="1" strokeLinecap="round" opacity="0.4"/>
      <path d="M11 19 Q14 18.2 17 19" stroke="var(--accent)" strokeWidth="1" strokeLinecap="round" opacity="0.4"/>
      <path d="M20 18 Q23 17.2 26 18" stroke="var(--accent)" strokeWidth="1" strokeLinecap="round" opacity="0.4"/>
    </svg>
  )
}
