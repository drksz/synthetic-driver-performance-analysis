


'''
DELIVERY DRIVER DATASET GENERATOR

A script that generates synthetic delivery data containing driver behavior and ratings. 
Values are randomly generated but with simulated statistical characteristics such as skew, 
outliers, correlation, etc. using internal parameters. 
'''


#Some constants for initial configuration
#For now, only 600 unique drivers and 120k deliveries are generated and loaded in chunks of 10k rows
NUM_DRIVERS = 600
NUM_DELIVERIES = 120_000
CHUNK_SIZE = 10_000
SEED = 42
DATE_START = "2026-01-01"
DATE_END = "2026-06-01"

FILE_OUT = "data/driver-deliveries.csv"


import pandas as pd
import numpy as np


rng = np.random.default_rng(SEED)

# HELPER FUNCS

def clamp_rating(values):
    return np.clip(values, 1.0, 5.0)

def generate_driver_ids(n):
    return np.array([f'DRV{100000+i}' for i in range(n)])

def generate_delivery_ids(start, n):
    return np.array([f'DEL{start+i:06d}' for i in range(n)])


'''
INTERNAL DRIVER TABLE

simply a reference table where the generated driver entities are stored.
this table will NOT be included in the output file.

contains parameters that introduces 'randomness'. for example:
   - service_quality influences attitude, pkg_care, responsiveness
   -traffic_violations may cause delays increasing arrival_act time
'''


def generate_drivers(n:int) -> pd.DataFrame:
    driver_ids = generate_driver_ids(n)

    # samples for the params below are drawn from a beta distribution 
    service_quality = rng.beta(8, 2, size=n)
    efficiency = rng.beta(7, 3, size=n)
    friendliness = rng.beta(8, 2, size=n)
    carefulness = rng.beta(8, 2, size=n)
    communication = rng.beta(8, 2, size=n)

    traffic_violations = rng.choice(
        [0, 1, 2, 3, 4, 5, 6, 7, 8], 
        size=n, 
        # using hardcoded probabilities for assigning violation count
        p=[0.68, 0.18, 0.07, 0.035, 0.02, 0.01, 0.003, 0.0015, 0.0005]
    )

    # using the violation count, we calculate a lambda value that represents accident risk 
    accident_lambda = 0.08 + (traffic_violations *0.13)
    # we then draw from a poission distribution using this lambda to simulate the number of accidents
    accident_cases = rng.poisson(accident_lambda)
    accident_cases = np.clip(accident_cases, 0, 5)

    return pd.DataFrame({
        'driver_id':driver_ids,
        'service_quality':service_quality,
        'efficiency':efficiency,
        'friendliness':friendliness,
        'carefulness':carefulness,
        'communication':communication,
        'traffic_violations':traffic_violations,
        'accident_cases':accident_cases
    })


def generate_weather_lookup() -> dict:

    # assigning probabilities of rain based on months Jan-June (labeled 1-6)
    # the probabilities used here mirror the Philippine climate system where the dry season
    # peaks around March with rain gradually arriving towards the second half of the year
    rain_probability = {
        1: 0.2, 
        2: 0.16, 
        3: 0.10,
        4: 0.15,
        5: 0.4, 
        6: 0.5
    }

    weather_by_date ={}

    for date in pd.date_range(DATE_START, DATE_END):
        month = date.month

        weather_by_date[date] = rng.choice(
            ['CLR','RAIN'],
            p=[
                1 - rain_probability[month],
                rain_probability[month]
            ]
        )

    return weather_by_date


'''
DELIVERIES TABLE 

the table to be generated. Samples from the drivers dataframe and uses internal parameters to create  
features such as delivery_spd, attitude, etc. Most features are generated with noise sampled from various
probability distributions to simulate real-world uncertainty. 
'''

def generate_chunk(chunk_start:int, chunk_size: int, drivers: pd.DataFrame, weather_by_date: dict) -> pd.DataFrame:
    driver_sample = drivers.sample(
        n=chunk_size,
        replace=True,
        random_state=int(rng.integers(0, 1_000_000))
    ).reset_index(drop=True)

    dates = pd.to_datetime(
        rng.choice(
            pd.date_range(DATE_START, DATE_END, freq='D'),
            size=chunk_size
        )
    )

    weekday = dates.dayofweek

    weather = pd.Series(dates).map(weather_by_date).to_numpy()

    traffic_cond = np.empty(chunk_size, dtype=object)

    # assign traffic conditions based on the day of the week. 
    # weekdays (0-4) are work days so traffic is usually slightly congested
    # but on weekends (5-6), there is a higher chance of traffic being lighter
    for i, day in enumerate(weekday):
        if day < 5:
            traffic_cond[i] = rng.choice(
                ['LIGHT','MEDIUM','HEAVY'],
                p=[0.15, 0.55, 0.3]
            )
        else:
            traffic_cond[i] = rng.choice(
                ['LIGHT','MEDIUM','HEAVY'],
                p=[0.35, 0.5, 0.15]
            )

    '''
    we map a numerical factor to each traffic and weather category. this simply 
    reduces or adds time dela to ETA 

    ex:

    base ETA = 30 mins

    LIGHT 
        30 * 0.85 = 25.5 mins (earlier arrival)
    MEDIUM
        30 * 1.00 = 30 mins (no change)
    HEAVY
        30 * 1.30 = 39 mins (later arrival)    
    '''
    traffic_multiplier = pd.Series(traffic_cond).map({
        'LIGHT': 0.85, 
        'MEDIUM': 1.00,
        'HEAVY': 1.30
    }).to_numpy()

    weather_multiplier = pd.Series(weather).map({
        'CLR': 1.0,
        'RAIN': 1.18
    }).to_numpy()

    # ETA simulations are drawn from a log-normal distribution since it introduces
    # a right skew and prevents time values from taking on values < 0
    base_eta = rng.lognormal(mean=3.45, sigma=0.42, size=chunk_size)
    arrival_est = base_eta * traffic_multiplier * weather_multiplier
    arrival_est = np.round(np.clip(arrival_est, 8, 150)).astype(int)

    driver_efficiency = driver_sample['efficiency'].to_numpy()
    violations = driver_sample['traffic_violations'].to_numpy()
    accidents = driver_sample['accident_cases'].to_numpy()

    delay_noise = rng.normal(loc=0, scale=8, size=chunk_size)


    # create base delay values using the internal parameters
    # some values were given some multipliers
    delay = (
        (1 - driver_efficiency) * 18
        + (traffic_multiplier - 1) * 25
        + (weather_multiplier - 1) * 20 
        + violations * 1.5
        + accidents * 2.5
        + delay_noise
    )

    # about two percent of the data will be given outlier values for the delay
    # again, drawn from a lognormal dist for skewness and high values
    outlier_mask = rng.random(chunk_size) < 0.02
    delay[outlier_mask] += rng.lognormal(
        mean=3.5, 
        sigma=0.45, 
        size=outlier_mask.sum()
    )

    # observations with early arrival have the delay value drawn from a uniform distribution
    # representing a 3 to 15 minute ahead of schedule delivery
    early_mask = rng.random(chunk_size) < 0.18
    delay[early_mask] -= rng.uniform(
        3, 
        15, 
        size=early_mask.sum()
    )

    arrival_act = arrival_est + np.round(delay).astype(int)
    arrival_act = np.clip(arrival_act, 5, 260)


    lateness = arrival_act - arrival_est

    service_quality = driver_sample['service_quality'].to_numpy()

    friendliness = driver_sample["friendliness"].to_numpy()
    carefulness = driver_sample["carefulness"].to_numpy()
    communication = driver_sample["communication"].to_numpy()

    # a base rating calculated using the internal parameters above
    # this works by assuming most drivers have a decent service quality rating
    # on top of individual personality characteristic scores 
    # and then adding Gaussian noise for variation
    rating_base = 3.0 + service_quality*1.3

    attitude = (
        rating_base 
        + friendliness * 0.5
        + rng.normal(0, 0.20, chunk_size)
    )
    pkg_care = (
        rating_base 
        + carefulness * 0.5
        + rng.normal(0, 0.25, chunk_size)
    )
    responsiveness = (
        rating_base 
        + communication * 0.5
        + rng.normal(0, 0.35, chunk_size)
    )

    # delivery_spd is penalized for every minute late. early deliveries get a small increase
    # driver_efficiency gives a significant boost and random noise is added to reflect customer perceptions
    delivery_spd = (
        4.8
        - np.maximum(lateness, 0) * 0.025
        + np.minimum(lateness, 0) * -0.005
        + driver_efficiency * 0.25
        + rng.normal(0, 0.30, chunk_size)
    )

    # here, we define a bad delivery event if it is an outlier (defined above)  
    # or if it took was more than 45 minutes late 
    bad_event_mask = (outlier_mask) | (lateness > 45)

    # we use this mask to subtract from attitude, responsiveness, and delivery_spd ratings
    # mirroring how customers would rate unpleasant service. The pkg_care value is left unpenalized
    # since not all late deliveries imply that a package is mishandled 
    attitude[bad_event_mask] -= rng.uniform(
        0.2, 
        0.8, 
        size=bad_event_mask.sum()
    )   

    responsiveness[bad_event_mask] -= rng.uniform(
        0.3, 
        1.0, 
        size=bad_event_mask.sum()
    )

    delivery_spd[bad_event_mask] -=rng.uniform(
        0.5, 
        1.3, 
        size=bad_event_mask.sum()
    ) 

    # building and returning the dataframe

    df = pd.DataFrame({
        'delivery_id': generate_delivery_ids(chunk_start+1, chunk_size),
        'driver_id': driver_sample['driver_id'],
        'date': dates.strftime('%Y-%m-%d'),
        'arrival_est': arrival_est,
        'arrival_act': arrival_act.astype(int),
        'attitude': clamp_rating(attitude),
        'pkg_care': clamp_rating(pkg_care),
        'responsiveness': clamp_rating(responsiveness),
        'delivery_spd': clamp_rating(delivery_spd),
        'weather': weather,
        'traffic_cond': traffic_cond
    })

    return df



# =========================
# VALIDATION
# =========================

def validate_dataset(df):
    print("\nDATASET SHAPE")
    print(df.shape)

    print("\nCOLUMNS")
    print(df.columns.tolist())

    print("\nMISSING VALUES")
    print(df.isnull().sum())

    print("\nUNIQUE DELIVERY IDS")
    print(df["delivery_id"].is_unique)

    print("\nDATE RANGE")
    print(df["date"].min(), "to", df["date"].max())

    print("\nWEATHER DISTRIBUTION")
    print(df["weather"].value_counts(normalize=True).round(3))

    print("\nTRAFFIC DISTRIBUTION")
    print(df["traffic_cond"].value_counts(normalize=True).round(3))

    print("\nNUMERIC SUMMARY")
    print(df.describe())

    print("\nCORRELATIONS")
    corr_cols = [
        "arrival_est",
        "arrival_act",
        "attitude",
        "pkg_care",
        "responsiveness",
        "delivery_spd"
    ]
    print(df[corr_cols].corr().round(3))

    delay = df["arrival_act"] - df["arrival_est"]
    print("\nDELAY SUMMARY")
    print(delay.describe().round(2))

    print("\nOUTLIER RATE: delay > 60 minutes")
    print(round((delay > 60).mean(), 4))




def main():
    drivers = generate_drivers(NUM_DRIVERS)
    weather_by_date = generate_weather_lookup()

    chunks = []

    generated = 0
    while generated < NUM_DELIVERIES:
        current_chunk_size = min(CHUNK_SIZE, NUM_DELIVERIES - generated)

        chunk = generate_chunk(
            chunk_start=generated,
            chunk_size=current_chunk_size,
            drivers=drivers,
            weather_by_date=weather_by_date
        )

        chunks.append(chunk)
        generated += current_chunk_size

        print(f"Generated {generated:,}/{NUM_DELIVERIES:,} rows")

    deliveries = pd.concat(chunks, ignore_index=True)

    validate_dataset(deliveries)

    deliveries.to_csv(FILE_OUT, index=False)

    print(f"\nExported final dataset to: {FILE_OUT}")


if __name__ == "__main__":
    main()
