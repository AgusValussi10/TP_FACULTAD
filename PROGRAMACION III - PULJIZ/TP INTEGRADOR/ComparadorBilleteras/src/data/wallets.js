export const WALLET_META = {
  'Mercado Pago': {
    color: '#00b1ea',
    initials: 'MP',
    description: 'La billetera más usada de Argentina. Sin comisión para PIX.',
    commission: '0%',
    dailyLimit: 'R$ 5.000',
    estimatedTime: 'Instantáneo',
    appUrl: 'https://mercadopago.com.ar',
  },
  'Ualá': {
    color: '#7c3aed',
    initials: 'UA',
    description: 'Tarjeta y billetera virtual con cuenta gratis.',
    commission: '0%',
    dailyLimit: 'R$ 3.000',
    estimatedTime: 'Instantáneo',
    appUrl: 'https://uala.com.ar',
  },
  'Bimo': {
    color: '#f59e0b',
    initials: 'BI',
    description: 'Billetera del Banco Macro con foco en pagos rápidos.',
    commission: '0%',
    dailyLimit: 'R$ 2.500',
    estimatedTime: 'Instantáneo',
    appUrl: 'https://bimo.com.ar',
  },
  'Prex': {
    color: '#06b6d4',
    initials: 'PX',
    description: 'Tarjeta Mastercard prepaga con app integrada.',
    commission: '0.5%',
    dailyLimit: 'R$ 3.000',
    estimatedTime: 'Instantáneo',
    appUrl: 'https://prexcard.com.ar',
  },
  'Naranja X': {
    color: '#f97316',
    initials: 'NX',
    description: 'Cuenta digital de Grupo Naranja con beneficios.',
    commission: '0%',
    dailyLimit: 'R$ 4.000',
    estimatedTime: 'Instantáneo',
    appUrl: 'https://naranjax.com',
  },
  'Brubank': {
    color: '#3b82f6',
    initials: 'BB',
    description: 'Banco 100% digital con cuenta gratuita.',
    commission: '0%',
    dailyLimit: 'R$ 3.500',
    estimatedTime: 'Instantáneo',
    appUrl: 'https://brubank.com',
  },
  'Personal Pay': {
    color: '#8b5cf6',
    initials: 'PP',
    description: 'Billetera virtual de Telecom Personal.',
    commission: '0.5%',
    dailyLimit: 'R$ 2.000',
    estimatedTime: 'Instantáneo',
    appUrl: 'https://personalpay.com.ar',
  },
  'Lemon Cash': {
    color: '#84cc16',
    initials: 'LC',
    description: 'Billetera cripto y fiat con tarjeta Visa.',
    commission: '1%',
    dailyLimit: 'R$ 5.000',
    estimatedTime: 'Hasta 5 min',
    appUrl: 'https://lemon.me',
  },
  'Modo': {
    color: '#ec4899',
    initials: 'MO',
    description: 'Plataforma de pagos de los bancos argentinos.',
    commission: '0%',
    dailyLimit: 'R$ 3.000',
    estimatedTime: 'Instantáneo',
    appUrl: 'https://modo.com.ar',
  },
  'Cuenta DNI': {
    color: '#0ea5e9',
    initials: 'CD',
    description: 'Billetera del Banco Provincia para todos los argentinos.',
    commission: '0%',
    dailyLimit: 'R$ 2.000',
    estimatedTime: 'Instantáneo',
    appUrl: 'https://cuentadni.bancoprovincia.com.ar',
  },
};

export const PROVIDERS = {
  BRL: [
    { id: '1', name: 'Mercado Pago', rate: 970.46 },
    { id: '2', name: 'Ualá', rate: 984.36 },
    { id: '3', name: 'Bimo', rate: 988.90 },
    { id: '4', name: 'Prex', rate: 997.40 },
    { id: '5', name: 'Naranja X', rate: 993.20 },
    { id: '6', name: 'Brubank', rate: 1000.46 },
    { id: '7', name: 'Personal Pay', rate: 1005.80 },
    { id: '8', name: 'Lemon Cash', rate: 1010.50 },
    { id: '9', name: 'Modo', rate: 1015.30 },
    { id: '10', name: 'Cuenta DNI', rate: 1020.75 },
  ],
};

export function formatARS(amount) {
  return `$ ${Math.round(amount).toLocaleString('es-AR')} ARS`;
}

export function buildResults(amount, currency = 'BRL') {
  const providers = PROVIDERS[currency] ?? PROVIDERS['BRL'];
  const sorted = [...providers].sort((a, b) => a.rate - b.rate);
  const worstPrice = sorted[sorted.length - 1].rate * amount;

  return sorted.map((p, index) => {
    const price = p.rate * amount;
    const savings = worstPrice - price;
    const savingsPct = (savings / worstPrice) * 100;
    return {
      ...p,
      price,
      savings: index < sorted.length - 1 ? savings : null,
      savingsPct: index < sorted.length - 1 ? savingsPct : null,
      isBest: index === 0,
    };
  });
}

export function getWalletMeta(name) {
  return (
    WALLET_META[name] ?? {
      color: '#6c757d',
      initials: name.slice(0, 2).toUpperCase(),
      description: '',
      commission: '—',
      dailyLimit: '—',
      estimatedTime: '—',
      appUrl: '',
    }
  );
}
