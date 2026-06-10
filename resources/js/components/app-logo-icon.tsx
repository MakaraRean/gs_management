export default function AppLogoIcon({ className }: { className?: string }) {
    return (
        <img
            src="/images/logo.jpg"
            alt="Gas Station Logo"
            className={className}
            style={{ objectFit: 'contain' }}
        />
    );
}
