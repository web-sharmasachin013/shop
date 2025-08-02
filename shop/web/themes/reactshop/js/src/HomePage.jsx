import React from "react";
import { useNavigate } from "react-router-dom";
import "./HomePage.css";

const products = [
  {
    id: 1,
    name: "Classic Shirt",
    price: "$29.99",
    image: "https://via.placeholder.com/200x200?text=Shirt",
  },
  {
    id: 2,
    name: "Blue Jeans",
    price: "$49.99",
    image: "https://via.placeholder.com/200x200?text=Jeans",
  },
  {
    id: 3,
    name: "Sneakers",
    price: "$59.99",
    image: "https://via.placeholder.com/200x200?text=Sneakers",
  },
  {
    id: 4,
    name: "Winter Jacket",
    price: "$99.99",
    image: "https://via.placeholder.com/200x200?text=Jacket",
  },
];

export default function HomePage() {
  const nav = useNavigate();

  const handleHomeNavigation = () => {
    nav("/");
  };
  return (
    <div className="homepage">
      <header className="header">
        <h1>MyShop</h1>
        <nav>
          <a href="#">Home</a>
          <a onClick={handleHomeNavigation}>Products</a>
          <a href="#">Cart</a>
        </nav>
      </header>

      <section className="hero">
        <h2>Welcome to MyShop</h2>
        <p>Get the best deals on the latest fashion</p>
        <button>Shop Now</button>
      </section>

      <section className="products">
        <h2>Featured Products</h2>
        <div className="product-grid">
          {products.map((product) => (
            <div key={product.id} className="product-card">
              <img src={product.image} alt={product.name} />
              <h3>{product.name}</h3>
              <p>{product.price}</p>
              <button>Add to Cart</button>
            </div>
          ))}
        </div>
      </section>

      <footer className="footer">
        <p>&copy; 2025 MyShop. All rights reserved.</p>
      </footer>
    </div>
  );
}
