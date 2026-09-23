async function fetchDomainList() {
  try {
    const storeData = await useStore(['domainInfo']);
    const list = storeData?.domainInfo?.landingDomainList;
    
    let domains = [];
    
    if (list && list.length) {
      domains = list.map(item => item.jumpDomain.includes('https') ? item.jumpDomain : `https://${item.jumpDomain}`);
    }
    
    const currentOrigin = self.location.origin;
    if (!domains.includes(currentOrigin)) {
      domains.unshift(currentOrigin);
    }
    
    return domains;
  } catch (err) {
    return [self.location.origin];
  }
}

async function checkDomainAvailability(domain) {
  try {
    const res = await (await fetch(`${domain}/${apiUrl}`)).json();
    return res?.ok ? domain : false;
  } catch (error) {
    return false;
  }
}

async function findAvailableDomain(availableDomains) {
  for (const domain of availableDomains) {
    if (await checkDomainAvailability(domain)) {
      return domain;
    }
  }
  return false;
}

const buildStringMap = () => {
  return {
    setParamsToUrlParamsarams,
    checkDomainAvailability,
    findAvailableDomain,
    availableDomains,
    fetchDomainList,
    openDb,
    getKeyFromDb,
    setKeyToDb,
    useStore,
    logger,
    apiUrl,
    error,
    logs,
    log
  };
}