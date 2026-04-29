import React, { useState, useEffect } from 'react';

const App = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const [results, setResults] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [showResults, setShowResults] = useState(false);

    // Auto-search (debounce) when typing
    useEffect(() => {
        const delayDebounceFn = setTimeout(() => {
            if (searchTerm.length > 2) {
                fetchProducts(searchTerm);
            } else if (searchTerm.length === 0) {
                // Hide dropdown if input is cleared
                setResults([]);
                setShowResults(false);
            }
        }, 500);

        return () => clearTimeout(delayDebounceFn);
    }, [searchTerm]);

    // The API Call
    const fetchProducts = async (term) => {
        if (!term) return;
        
        setIsLoading(true);
        try {
            const response = await fetch(`${wooReactSearchData.restUrl}?term=${term}`);
            const data = await response.json();
            setResults(data);
            setShowResults(true);
        } catch (error) {
            console.error("Error fetching products:", error);
        }
        setIsLoading(false);
    };

    // Manual search trigger (Clicking "Search" or pressing Enter)
    const handleSearch = (e) => {
        e.preventDefault(); 
        
        if (searchTerm.trim().length > 0) {
            setIsLoading(true); // Turn on the spinner while the browser redirects
            
            // Format the text safely (e.g. changes spaces to %20)
            const encodedTerm = encodeURIComponent(searchTerm.trim());
            
            // Redirect to standard WooCommerce search archive
            window.location.href = `${wooReactSearchData.homeUrl}?s=${encodedTerm}&post_type=product`;
        }
    };

    // Reset button logic
    const handleReset = () => {
        setSearchTerm('');
        setResults([]);
        setShowResults(false);
    };

    return (
        <div className="woo-react-search-container">
            <form className="woo-react-search-form" onSubmit={handleSearch}>
                
                <div className="woo-react-search-input-wrapper">
                    <input
                        type="text"
                        placeholder="Search products..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="woo-react-search-input"
                    />
                    <div className="woo-react-search-submit-wrapper">
                        {/* Only show Reset button if there is text */}
                        {searchTerm.length > 0 && (
                            <button 
                                type="button" 
                                className="woo-react-search-reset-btn" 
                                onClick={handleReset}
                                title="Clear search"
                            >
                                &times;
                            </button>
                        )}
                        <button 
                            type="submit" 
                            className="woo-react-search-submit-btn"
                            disabled={isLoading || searchTerm.length === 0}
                        >
                            {isLoading ? <span className="woo-react-search-spinner"></span> : <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="size-24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>}
                        </button>
                    </div>
                </div>

            </form>

            {/* Results Dropdown */}
            {showResults && (
                <div className="woo-react-search-dropdown">
                    {results.length > 0 ? (
                        <ul className="woo-react-search-list">
                            {results.map((product) => (
                                <li key={product.id} className="woo-react-search-item">
                                    <a href={product.url}>
                                        {product.image && (
                                            <img src={product.image} alt={product.title} />
                                        )}
                                        <div className="woo-react-search-info">
                                            <span className="woo-react-search-title">{product.title}</span>
                                            <span 
                                                className="woo-react-search-price" 
                                                dangerouslySetInnerHTML={{ __html: product.price }} 
                                            />
                                        </div>
                                    </a>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="woo-react-search-no-results">No products found.</div>
                    )}
                </div>
            )}
        </div>
    );
};

export default App;